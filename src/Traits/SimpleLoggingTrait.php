<?php

namespace Frddl\LaravelSimpleLogging\Traits;

use Frddl\LaravelSimpleLogging\Models\LogEntry;
use Illuminate\Support\Facades\Log;

trait SimpleLoggingTrait
{
    /**
     * Ultra-simple logging - just pass data and it figures out the rest
     * NO DEPENDENCIES - works with ANY data
     */
    protected function log($message, $data = [], $level = 'info')
    {
        if (!$this->isLoggingEnabled()) {
            return;
        }

        $this->logToDatabase($message, $data, $level);
        
        if (config('logging_trait.file_logging', false)) {
            Log::log($level, $message, $data);
        }
    }

    /**
     * Get or create a consistent request ID for the entire request
     */
    private function getRequestId()
    {
        static $requestId = null;
        
        if ($requestId === null) {
            $requestId = request()->header('X-Request-ID', uniqid());
        }
        
        return $requestId;
    }

    /**
     * Wrap method execution with automatic logging
     * Usage: return $this->logMethod('Method Name', $data, function() { ... });
     */
    protected function logMethod($methodName, $inputData = [], $callback, $options = [])
    {
        if (!$this->isLoggingEnabled()) {
            return $callback();
        }

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        $this->log("{$methodName} started", $inputData, 'info');

        try {
            $result = $callback();
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $memoryUsed = memory_get_usage(true) - $startMemory;
            
            $this->log("{$methodName} completed", [
                'duration_ms' => $duration,
                'memory_used' => $memoryUsed,
                'response_data' => $this->extractResponseData($result),
            ] + $inputData, 'info');
            
            return $result;
            
        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $memoryUsed = memory_get_usage(true) - $startMemory;
            
            $this->log("{$methodName} failed", [
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
                'memory_used' => $memoryUsed,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ] + $inputData, 'error');
            
            throw $e; // Re-throw the exception
        }
    }

    /**
     * Log to database - completely generic
     */
    private function logToDatabase($message, $data, $level)
    {
        try {
            $sanitizedData = $this->sanitizeData($data);
            
            LogEntry::create([
                'request_id' => $this->getRequestId(),
                'level' => $level,
                'message' => $message,
                'context' => $sanitizedData,
                'properties' => $sanitizedData, // Store sanitized data as properties
                'controller' => class_basename($this),
                'method' => $this->getCallingMethod(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'url' => request()->fullUrl(),
                'http_method' => request()->method(),
                'status_code' => 200,
                'duration' => $data['duration_ms'] ?? null,
                'memory_usage' => memory_get_usage(true),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Silent fail - don't break the app
        }
    }

    /**
     * Sanitize sensitive data while preserving field names
     */
    private function sanitizeData($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        $sensitiveFields = [
            'password', 'passwd', 'pwd', 'pass',
            'token', 'access_token', 'refresh_token', 'api_token', 'auth_token',
            'secret', 'key', 'private_key', 'secret_key',
            'credit_card', 'card_number', 'cvv', 'cvc',
            'ssn', 'social_security',
            'email', 'phone', 'telephone',
            'authorization', 'auth',
            'session_id', 'sessionid',
            'cookie', 'cookies',
            'x-api-key', 'x-auth-token',
            'bearer', 'jwt'
        ];

        $sanitized = [];
        foreach ($data as $key => $value) {
            $lowerKey = strtolower($key);
            
            // Check if this field should be sanitized
            $shouldSanitize = false;
            foreach ($sensitiveFields as $sensitiveField) {
                if (strpos($lowerKey, $sensitiveField) !== false) {
                    $shouldSanitize = true;
                    break;
                }
            }
            
            if ($shouldSanitize) {
                // Preserve the field but mask the value
                if (is_string($value) && strlen($value) > 0) {
                    // Special handling for authorization headers with Bearer prefix
                    if ($lowerKey === 'authorization' && strpos($value, 'Bearer ') === 0) {
                        $sanitized[$key] = 'Bearer [HIDDEN]';
                    } else {
                        $sanitized[$key] = $this->maskValue($value);
                    }
                } elseif (is_array($value)) {
                    // Special handling for authorization headers in arrays
                    if ($lowerKey === 'authorization') {
                        $sanitized[$key] = array_map(function($val) {
                            if (is_string($val) && strpos($val, 'Bearer ') === 0) {
                                return 'Bearer [HIDDEN]';
                            }
                            return '[HIDDEN]';
                        }, $value);
                    } else {
                        $sanitized[$key] = $this->sanitizeData($value);
                    }
                } else {
                    $sanitized[$key] = '[MASKED]';
                }
            } elseif (is_array($value)) {
                // Recursively sanitize nested arrays
                $sanitized[$key] = $this->sanitizeData($value);
            } else {
                // Keep the original value
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }

    /**
     * Mask sensitive values - completely hide them
     */
    private function maskValue($value)
    {
        if (empty($value)) {
            return '[EMPTY]';
        }
        
        // For complete sanitization, just show the field exists but hide the value
        return '[HIDDEN]';
    }

    /**
     * Extract response data from various response types
     */
    private function extractResponseData($result)
    {
        if (is_array($result)) {
            return $result;
        }
        
        if (is_object($result)) {
            // Handle Laravel Response objects
            if (method_exists($result, 'getData')) {
                $data = $result->getData(true);
                return is_array($data) ? $data : ['data' => $data];
            }
            
            // Handle JSON responses
            if (method_exists($result, 'getContent')) {
                $content = $result->getContent();
                $decoded = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
                return ['content' => $content];
            }
            
            // Convert object to array
            return (array) $result;
        }
        
        if (is_string($result)) {
            $decoded = json_decode($result, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
            return ['content' => $result];
        }
        
        return ['value' => $result];
    }

    /**
     * Get the actual calling method (not the log method)
     */
    private function getCallingMethod()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);
        
        // Look for the first method that's not from this trait
        foreach ($trace as $index => $frame) {
            if (isset($frame['class']) && isset($frame['function'])) {
                $class = $frame['class'];
                $function = $frame['function'];
                
                // Skip methods from this trait and its internal methods
                if (strpos($class, 'SimpleLoggingTrait') !== false || 
                    $function === 'getCallingMethod' ||
                    $function === 'logToDatabase' ||
                    $function === 'logMethod' ||
                    $function === 'log') {
                    continue;
                }
                
                // Skip internal PHP methods
                if (strpos($class, 'Closure') !== false || 
                    strpos($class, 'Reflection') !== false ||
                    strpos($class, 'Illuminate\\') !== false) {
                    continue;
                }
                
                // Return the first controller method we find
                if (strpos($class, 'Controller') !== false) {
                    return $function;
                }
            }
        }
        
        return 'unknown';
    }

    /**
     * Check if logging is enabled
     */
    private function isLoggingEnabled()
    {
        return config('logging_trait.enabled', true);
    }
}
