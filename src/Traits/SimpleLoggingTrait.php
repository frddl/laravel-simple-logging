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
    protected function log($message, $data = [], $level = 'info', $logType = 'action')
    {
        if (! $this->isLoggingEnabled()) {
            return;
        }

        // Check if the log level meets the minimum threshold
        if (! $this->shouldLogLevel($level)) {
            return;
        }

        // Add log type and visual indicators to data
        $enhancedData = array_merge($data, [
            'log_type' => $logType,
            'visual_indicator' => $this->getVisualIndicator($logType, $level, $message),
            'category' => $this->getLogCategory($logType),
        ]);

        // Log to database if enabled
        if (config('simple-logging.database_logging', true)) {
            $this->logToDatabase($message, $enhancedData, $level);
        }

        // Log to file if enabled
        if (config('simple-logging.file_logging', false)) {
            Log::log($level, $message, $enhancedData);
        }
    }

    /**
     * Log method start - call this at the beginning of a method
     */
    protected function logMethodStart($methodName, $data = [])
    {
        $this->pushCallStack();
        $this->log($methodName . ' started', $data, 'info', 'action');
    }

    /**
     * Log method end - call this at the end of a method
     */
    protected function logMethodEnd($methodName, $data = [])
    {
        $this->log($methodName . ' completed', $data, 'info', 'action');
        $this->popCallStack();
    }

    /**
     * Convenience methods for different log levels
     */
    protected function logInfo($message, $data = [])
    {
        $this->log($message, $data, 'info', 'action');
    }

    protected function logWarning($message, $data = [])
    {
        $this->log($message, $data, 'warning', 'action');
    }

    protected function logError($message, $data = [])
    {
        $this->log($message, $data, 'error', 'action');
    }

    protected function logDebug($message, $data = [])
    {
        $this->log($message, $data, 'debug', 'action');
    }

    protected function logSuccess($message, $data = [])
    {
        $this->log($message, $data, 'success', 'action');
    }

    protected function logCritical($message, $data = [])
    {
        $this->log($message, $data, 'critical', 'action');
    }

    protected function logAlert($message, $data = [])
    {
        $this->log($message, $data, 'alert', 'action');
    }

    protected function logEmergency($message, $data = [])
    {
        $this->log($message, $data, 'emergency', 'action');
    }

    /**
     * Get or create a consistent request ID for the entire request
     */
    private function getRequestId()
    {
        static $requestId = null;

        if ($requestId === null) {
            try {
                $requestId = request()->header('X-Request-ID', uniqid());
            } catch (\Exception $e) {
                // In test environment or when request is not available
                $requestId = uniqid('test-');
            }
        }

        return $requestId;
    }

    /**
     * Get or create a consistent entry method for the entire request
     * This captures the FIRST method that calls logMethod in a request cycle
     */
    private function getEntryMethod()
    {
        if (app()->bound('simple-logging.entry-method')) {
            return app('simple-logging.entry-method');
        }

        return 'unknown';
    }

    /**
     * Set the entry method (only if not already set)
     * This ensures we capture the FIRST method that calls logMethod
     */
    private function setEntryMethod($methodName)
    {
        if (! app()->bound('simple-logging.entry-method')) {
            app()->instance('simple-logging.entry-method', $methodName);
        }
    }

    /**
     * Wrap method execution with automatic logging
     * Usage: return $this->logMethod('Method Name', $data, function() { ... });
     */
    protected function logMethod($methodName, $inputData = [], $callback = null, $options = [])
    {
        // Handle the case where callback is passed as second parameter
        if (is_callable($inputData)) {
            $callback = $inputData;
            $inputData = [];
        }

        // Set the entry method if this is the first logMethod call in this request
        $this->setEntryMethod($methodName);

        if (! $this->isLoggingEnabled()) {
            return $callback();
        }

        // Push to call stack at the start
        $this->pushCallStack();

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

            // Pop from call stack at the end
            $this->popCallStack();

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

            // Pop from call stack even on error
            $this->popCallStack();

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

            // Get request info safely
            $requestInfo = $this->getEnhancedRequestInfo();

            LogEntry::create([
                'request_id' => $this->getRequestId(),
                'level' => $level,
                'message' => $message,
                'context' => array_merge($sanitizedData, $requestInfo),
                'properties' => array_merge($sanitizedData, $requestInfo), // Store sanitized data as properties
                'controller' => class_basename($this),
                'method' => $this->getEntryMethod(), // Use the first method that called logMethod
                'call_depth' => $this->getCallDepth(),
                'ip_address' => $requestInfo['request_info']['ip_address'] ?? '127.0.0.1',
                'user_agent' => $requestInfo['request_info']['user_agent'] ?? 'Test Agent',
                'url' => $requestInfo['request_info']['url'] ?? 'http://test.local',
                'http_method' => $requestInfo['request_info']['method'] ?? 'TEST',
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
        if (! is_array($data)) {
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
            'bearer', 'jwt',
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
                        $sanitized[$key] = array_map(function ($val) {
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
                $responseData = is_array($data) ? $data : ['data' => $data];

                // Add HTTP response metadata
                $responseData['status_code'] = method_exists($result, 'getStatusCode') ? $result->getStatusCode() : null;
                $responseData['content_type'] = method_exists($result, 'headers') ? $result->headers->get('Content-Type') : null;

                return $responseData;
            }

            // Handle JSON responses
            if (method_exists($result, 'getContent')) {
                $content = $result->getContent();
                $decoded = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $responseData = $decoded;
                } else {
                    $responseData = ['content' => $content];
                }

                // Add HTTP response metadata
                $responseData['status_code'] = method_exists($result, 'getStatusCode') ? $result->getStatusCode() : null;
                $responseData['content_type'] = method_exists($result, 'headers') ? $result->headers->get('Content-Type') : null;

                return $responseData;
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
        return config('simple-logging.enabled', true);
    }

    /**
     * Check if the log level meets the minimum threshold
     */
    private function shouldLogLevel($level)
    {
        $minLevel = config('simple-logging.log_level', 'info');

        $levels = [
            'debug' => 0,
            'info' => 1,
            'notice' => 2,
            'warning' => 3,
            'error' => 4,
            'critical' => 5,
            'alert' => 6,
            'emergency' => 7,
        ];

        $currentLevel = $levels[$level] ?? 1;
        $minLevelValue = $levels[$minLevel] ?? 1;

        return $currentLevel >= $minLevelValue;
    }

    /**
     * Determine log type based on method name and options
     */
    private function determineLogType($methodName, $options = [])
    {
        // Check if log type is explicitly specified in options
        if (isset($options['log_type'])) {
            return $options['log_type'];
        }

        // Determine based on method name patterns
        $methodName = strtolower($methodName);

        // Function calls (methods that start with 'get', 'set', 'create', 'update', 'delete', etc.)
        if (preg_match('/^(get|set|create|update|delete|find|search|load|fetch|retrieve|build|format|process|validate|check|verify)/', $methodName)) {
            return 'function_call';
        }

        // Business logic (methods that contain 'logic', 'process', 'handle', 'execute')
        if (preg_match('/(logic|process|handle|execute|business|workflow|flow)/', $methodName)) {
            return 'business_logic';
        }

        // Database operations
        if (preg_match('/(query|database|db|sql|model|save|store)/', $methodName)) {
            return 'database_operation';
        }

        // API/HTTP operations
        if (preg_match('/(api|http|request|response|endpoint|route)/', $methodName)) {
            return 'api_operation';
        }

        // Configuration/Setup
        if (preg_match('/(config|setup|init|initialize|configure|install)/', $methodName)) {
            return 'configuration';
        }

        // Default to action
        return 'action';
    }

    /**
     * Get visual indicator based on log type and level
     */
    private function getVisualIndicator($logType, $level, $message = '')
    {
        $indicators = [
            'function_call' => [
                'started' => '🔵',
                'completed' => '✅',
                'failed' => '❌',
                'default' => '⚙️',
            ],
            'business_logic' => [
                'started' => '🟡',
                'completed' => '✅',
                'failed' => '❌',
                'default' => '🧠',
            ],
            'database_operation' => [
                'started' => '🔵',
                'completed' => '✅',
                'failed' => '❌',
                'default' => '🗄️',
            ],
            'api_operation' => [
                'started' => '🟢',
                'completed' => '✅',
                'failed' => '❌',
                'default' => '🌐',
            ],
            'configuration' => [
                'started' => '🟣',
                'completed' => '✅',
                'failed' => '❌',
                'default' => '⚙️',
            ],
            'action' => [
                'started' => '🟠',
                'completed' => '✅',
                'failed' => '❌',
                'default' => '📝',
            ],
        ];

        $typeIndicators = $indicators[$logType] ?? $indicators['action'];

        // Check if message contains status keywords
        if (strpos($message, 'started') !== false) {
            return $typeIndicators['started'];
        } elseif (strpos($message, 'completed') !== false) {
            return $typeIndicators['completed'];
        } elseif (strpos($message, 'failed') !== false) {
            return $typeIndicators['failed'];
        }

        return $typeIndicators['default'];
    }

    /**
     * Get log category for better organization
     */
    private function getLogCategory($logType)
    {
        $categories = [
            'function_call' => 'Function Calls',
            'business_logic' => 'Business Logic',
            'database_operation' => 'Database Operations',
            'api_operation' => 'API Operations',
            'configuration' => 'Configuration',
            'action' => 'Actions',
        ];

        return $categories[$logType] ?? 'Actions';
    }

    /**
     * Get enhanced request information
     */
    private function getEnhancedRequestInfo()
    {
        try {
            $request = request();

            return [
                'request_info' => [
                    'method' => $request->method(),
                    'url' => $request->fullUrl(),
                    'path' => $request->path(),
                    'query_params' => $request->query(),
                    'route_name' => $request->route() ? $request->route()->getName() : null,
                    'route_action' => $request->route() ? $request->route()->getActionName() : null,
                    'is_ajax' => $request->ajax(),
                    'is_json' => $request->isJson(),
                    'wants_json' => $request->wantsJson(),
                    'content_type' => $request->header('Content-Type'),
                    'accept' => $request->header('Accept'),
                    'referer' => $request->header('Referer'),
                    'origin' => $request->header('Origin'),
                    'x_requested_with' => $request->header('X-Requested-With'),
                    'x_forwarded_for' => $request->header('X-Forwarded-For'),
                    'x_real_ip' => $request->header('X-Real-IP'),
                    'user_agent' => $request->userAgent(),
                    'ip_address' => $request->ip(),
                    'server_name' => $request->server('SERVER_NAME'),
                    'server_port' => $request->server('SERVER_PORT'),
                    'https' => $request->secure(),
                    'timestamp' => now()->toISOString(),
                ],
                'headers' => $this->sanitizeHeaders($request->headers->all()),
                'input_data' => $this->sanitizeData($request->all()),
                'session_id' => session()->getId(),
                'user_id' => auth()->id(),
            ];
        } catch (\Exception $e) {
            // In test environment or when request is not available
            return [
                'request_info' => [
                    'method' => 'TEST',
                    'url' => 'http://test.local',
                    'path' => '/test',
                    'query_params' => [],
                    'route_name' => null,
                    'route_action' => null,
                    'is_ajax' => false,
                    'is_json' => false,
                    'wants_json' => false,
                    'content_type' => null,
                    'accept' => null,
                    'referer' => null,
                    'origin' => null,
                    'x_requested_with' => null,
                    'x_forwarded_for' => null,
                    'x_real_ip' => null,
                    'user_agent' => 'Test Agent',
                    'ip_address' => '127.0.0.1',
                    'server_name' => 'test.local',
                    'server_port' => '80',
                    'https' => false,
                    'timestamp' => now()->toISOString(),
                ],
                'headers' => [],
                'input_data' => [],
                'session_id' => 'test-session',
                'user_id' => null,
            ];
        }
    }

    /**
     * Sanitize headers to remove sensitive information
     */
    private function sanitizeHeaders($headers)
    {
        $sensitiveHeaders = [
            'authorization',
            'cookie',
            'x-api-key',
            'x-auth-token',
            'x-csrf-token',
            'x-session-token',
        ];

        $sanitized = [];
        foreach ($headers as $key => $value) {
            $lowerKey = strtolower($key);
            if (in_array($lowerKey, $sensitiveHeaders)) {
                $sanitized[$key] = ['***REDACTED***'];
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Get the call stacks (shared static variable)
     */
    private function &getCallStacks()
    {
        static $callStacks = [];

        return $callStacks;
    }

    /**
     * Calculate the call depth based on a stack-based approach
     */
    private function getCallDepth()
    {
        $callStacks = &$this->getCallStacks();
        $requestId = $this->getRequestId();

        // Initialize call stack for this request
        if (! isset($callStacks[$requestId])) {
            $callStacks[$requestId] = [];
        }

        // Get the current call stack depth
        $currentDepth = count($callStacks[$requestId]);


        // Clean up old request stacks (keep only last 10 requests)
        if (count($callStacks) > 10) {
            $callStacks = array_slice($callStacks, -10, null, true);
        }

        return max(1, $currentDepth);
    }

    /**
     * Push a new call onto the stack (call this at the start of a method)
     */
    private function pushCallStack()
    {
        $callStacks = &$this->getCallStacks();
        $requestId = $this->getRequestId();

        // Initialize call stack for this request
        if (! isset($callStacks[$requestId])) {
            $callStacks[$requestId] = [];
        }

        // Push current timestamp and method info onto stack
        $callStacks[$requestId][] = [
            'timestamp' => microtime(true),
            'method' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)[1]['function'] ?? 'unknown',
        ];

        // Clean up old request stacks
        if (count($callStacks) > 10) {
            $callStacks = array_slice($callStacks, -10, null, true);
        }
    }

    /**
     * Pop a call from the stack (call this at the end of a method)
     */
    private function popCallStack()
    {
        $callStacks = &$this->getCallStacks();
        $requestId = $this->getRequestId();

        if (isset($callStacks[$requestId]) && ! empty($callStacks[$requestId])) {
            array_pop($callStacks[$requestId]);
        }
    }
}
