<?php

namespace Frddl\LaravelSimpleLogging\Http\Controllers;

use Carbon\Carbon;
use Frddl\LaravelSimpleLogging\Models\LogEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LogViewerController extends Controller
{
    /**
     * Display the log viewer dashboard
     */
    public function index(Request $request): View
    {
        $levels = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];

        // Get grouped logs for initial display
        $perPage = $request->get('per_page', 10);
        $groupedLogs = $this->getGroupedLogs($request, $perPage);

        // Get statistics for initial display
        $statistics = $this->getStatistics($request);

        return view('simple-logging::index', compact('levels', 'groupedLogs', 'statistics'));
    }

    /**
     * Get grouped logs for display
     */
    private function getGroupedLogs(Request $request, int $perPage = 10): array
    {
        $page = $request->get('page', 1);
        $offset = ($page - 1) * $perPage;

        // Get unique request_ids ordered by their latest log timestamp
        $requestQuery = LogEntry::query();

        // Apply filters
        if ($request->filled('level')) {
            $requestQuery->where('level', $request->level);
        }
        if ($request->filled('type')) {
            $requestQuery->whereJsonContains('context->type', $request->type);
        }
        if ($request->filled('date_from')) {
            $requestQuery->where('created_at', '>=', Carbon::parse($request->date_from));
        }
        if ($request->filled('date_to')) {
            $requestQuery->where('created_at', '<=', Carbon::parse($request->date_to));
        }
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $requestQuery->where(function ($q) use ($searchTerm) {
                $q->where('message', 'like', '%' . $searchTerm . '%')
                  ->orWhere('context', 'like', '%' . $searchTerm . '%')
                  ->orWhere('properties', 'like', '%' . $searchTerm . '%')
                  ->orWhere('request_id', 'like', '%' . $searchTerm . '%')
                  ->orWhere('controller', 'like', '%' . $searchTerm . '%')
                  ->orWhere('method', 'like', '%' . $searchTerm . '%')
                  ->orWhere('url', 'like', '%' . $searchTerm . '%')
                  ->orWhere('ip_address', 'like', '%' . $searchTerm . '%');
            });
        }
        if ($request->filled('property_key') && $request->filled('property_value')) {
            $requestQuery->whereJsonContains('properties->' . $request->property_key, $request->property_value);
        }
        if ($request->filled('has_property')) {
            $requestQuery->whereNotNull('properties->' . $request->has_property);
        }
        if ($request->filled('property_search')) {
            $requestQuery->where('properties', 'like', '%' . $request->property_search . '%');
        }

        $requestIds = $requestQuery->select('request_id')
            ->selectRaw('MAX(created_at) as latest_log_time')
            ->groupBy('request_id')
            ->orderBy('latest_log_time', 'desc')
            ->offset($offset)
            ->limit($perPage)
            ->pluck('request_id')
            ->toArray();

        // Get total count for pagination
        $totalQuery = LogEntry::query();

        // Apply same filters to total count query
        if ($request->filled('level')) {
            $totalQuery->where('level', $request->level);
        }
        if ($request->filled('type')) {
            $totalQuery->whereJsonContains('context->type', $request->type);
        }
        if ($request->filled('date_from')) {
            $totalQuery->where('created_at', '>=', Carbon::parse($request->date_from));
        }
        if ($request->filled('date_to')) {
            $totalQuery->where('created_at', '<=', Carbon::parse($request->date_to));
        }
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $totalQuery->where(function ($q) use ($searchTerm) {
                $q->where('message', 'like', '%' . $searchTerm . '%')
                  ->orWhere('context', 'like', '%' . $searchTerm . '%')
                  ->orWhere('properties', 'like', '%' . $searchTerm . '%')
                  ->orWhere('request_id', 'like', '%' . $searchTerm . '%')
                  ->orWhere('controller', 'like', '%' . $searchTerm . '%')
                  ->orWhere('method', 'like', '%' . $searchTerm . '%')
                  ->orWhere('url', 'like', '%' . $searchTerm . '%')
                  ->orWhere('ip_address', 'like', '%' . $searchTerm . '%');
            });
        }
        if ($request->filled('property_key') && $request->filled('property_value')) {
            $totalQuery->whereJsonContains('properties->' . $request->property_key, $request->property_value);
        }
        if ($request->filled('has_property')) {
            $totalQuery->whereNotNull('properties->' . $request->has_property);
        }
        if ($request->filled('property_search')) {
            $totalQuery->where('properties', 'like', '%' . $request->property_search . '%');
        }

        $totalRequests = $totalQuery->select('request_id')->distinct()->get()->count();

        // Get all logs for these request_ids, maintaining the order from $requestIds
        $logs = collect($requestIds)->map(function ($requestId) {
            return LogEntry::where('request_id', $requestId)
                ->orderBy('created_at', 'asc')
                ->get()
                ->toArray();
        })->toArray();

        // Process logs for display
        $processedLogs = $this->processLogsForDisplay($logs);

        return [
            'logs' => $processedLogs,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $totalRequests,
                'last_page' => ceil($totalRequests / $perPage),
                'has_more' => $page < ceil($totalRequests / $perPage),
            ],
            'statistics' => $this->getStatistics($request),
        ];
    }

    /**
     * Get detailed log data for a specific request ID
     */
    public function getLogDetails(Request $request)
    {
        try {
            $requestId = $request->get('request_id');

            if (! $requestId) {
                return response()->json(['error' => 'Request ID is required'], 400);
            }

            // Get all logs for this request ID
            $logs = LogEntry::where('request_id', $requestId)
                ->orderBy('created_at', 'asc')
                ->get()
                ->toArray();

            if (empty($logs)) {
                return response()->json(['error' => 'No logs found for this request ID'], 404);
            }

            // Process the logs for detailed display
            $processedData = $this->processDetailedLogData($logs);

            // Return view with compact data
            return view('simple-logging::partials.log-details-content', [
                'data' => [
                    'steps' => $processedData['steps'] ?? [],
                    'request_info' => $processedData['request_info'] ?? [],
                    'response_info' => $processedData['response_data'] ?? [],
                    'all_responses' => $processedData['all_responses'] ?? [],
                    'summary' => $processedData['summary'] ?? [],
                ],
                'meta' => [
                    'steps_count' => count($processedData['steps'] ?? []),
                    'has_steps' => ! empty($processedData['steps']),
                    'processed_at' => now()->toISOString(),
                ],
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error in getLogDetails: ' . $e->getMessage(), [
                'request_id' => $request->get('request_id'),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Internal server error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get logs with filters
     */
    public function getLogs(Request $request): JsonResponse
    {
        // If only count is requested, return just the count
        if ($request->boolean('count_only')) {
            $query = LogEntry::query();

            // Apply basic filters for count
            if ($request->filled('level')) {
                $query->where('level', $request->level);
            }

            if ($request->filled('type')) {
                $query->whereJsonContains('context->type', $request->type);
            }

            if ($request->filled('date_from')) {
                $query->where('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->where('created_at', '<=', $request->date_to);
            }

            if ($request->filled('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('message', 'like', '%' . $request->search . '%')
                      ->orWhere('context', 'like', '%' . $request->search . '%')
                      ->orWhere('properties', 'like', '%' . $request->search . '%');
                });
            }

            $totalLogs = $query->count();

            return response()->json([
                'total_logs' => $totalLogs,
            ]);
        }

        $query = LogEntry::query();

        // Apply filters

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        if ($request->filled('type')) {
            $query->whereJsonContains('context->type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', Carbon::parse($request->date_from));
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', Carbon::parse($request->date_to));
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('message', 'like', '%' . $searchTerm . '%')
                  ->orWhere('context', 'like', '%' . $searchTerm . '%')
                  ->orWhere('properties', 'like', '%' . $searchTerm . '%')
                  ->orWhere('request_id', 'like', '%' . $searchTerm . '%')
                  ->orWhere('controller', 'like', '%' . $searchTerm . '%')
                  ->orWhere('method', 'like', '%' . $searchTerm . '%')
                  ->orWhere('url', 'like', '%' . $searchTerm . '%')
                  ->orWhere('ip_address', 'like', '%' . $searchTerm . '%');
            });
        }

        // Custom property filters
        if ($request->filled('property_key') && $request->filled('property_value')) {
            $query->whereJsonContains('properties->' . $request->property_key, $request->property_value);
        }

        // Filter by specific property keys
        if ($request->filled('has_property')) {
            $query->whereNotNull('properties->' . $request->has_property);
        }

        // Filter by property value (searches in all properties)
        if ($request->filled('property_search')) {
            $query->where('properties', 'like', '%' . $request->property_search . '%');
        }

        // Check if grouping by request_id is requested
        if ($request->boolean('group_by_request')) {
            $perPage = $request->get('per_page', config('simple-logging.viewer.per_page', 50));
            $page = $request->get('page', 1);
            $offset = ($page - 1) * $perPage;

            // Get unique request_ids ordered by their latest log timestamp
            // Create a fresh query to avoid conflicts with existing ordering
            $requestQuery = LogEntry::query();

            // Apply same filters to request query
            if ($request->filled('level')) {
                $requestQuery->where('level', $request->level);
            }
            if ($request->filled('type')) {
                $requestQuery->whereJsonContains('context->type', $request->type);
            }
            if ($request->filled('date_from')) {
                $requestQuery->where('created_at', '>=', Carbon::parse($request->date_from));
            }
            if ($request->filled('date_to')) {
                $requestQuery->where('created_at', '<=', Carbon::parse($request->date_to));
            }
            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $requestQuery->where(function ($q) use ($searchTerm) {
                    $q->where('message', 'like', '%' . $searchTerm . '%')
                      ->orWhere('context', 'like', '%' . $searchTerm . '%')
                      ->orWhere('properties', 'like', '%' . $searchTerm . '%')
                      ->orWhere('request_id', 'like', '%' . $searchTerm . '%')
                      ->orWhere('controller', 'like', '%' . $searchTerm . '%')
                      ->orWhere('method', 'like', '%' . $searchTerm . '%')
                      ->orWhere('url', 'like', '%' . $searchTerm . '%')
                      ->orWhere('ip_address', 'like', '%' . $searchTerm . '%');
                });
            }
            if ($request->filled('property_key') && $request->filled('property_value')) {
                $requestQuery->whereJsonContains('properties->' . $request->property_key, $request->property_value);
            }
            if ($request->filled('has_property')) {
                $requestQuery->whereNotNull('properties->' . $request->has_property);
            }
            if ($request->filled('property_search')) {
                $requestQuery->where('properties', 'like', '%' . $request->property_search . '%');
            }

            $requestIds = $requestQuery->select('request_id')
                ->selectRaw('MAX(created_at) as latest_log_time')
                ->groupBy('request_id')
                ->orderBy('latest_log_time', 'desc')
                ->offset($offset)
                ->limit($perPage)
                ->pluck('request_id')
                ->toArray();

            // Get total count for pagination (recreate query with same filters)
            $totalQuery = LogEntry::query();

            // Apply same filters to total count query
            if ($request->filled('level')) {
                $totalQuery->where('level', $request->level);
            }
            if ($request->filled('type')) {
                $totalQuery->whereJsonContains('context->type', $request->type);
            }
            if ($request->filled('date_from')) {
                $totalQuery->where('created_at', '>=', Carbon::parse($request->date_from));
            }
            if ($request->filled('date_to')) {
                $totalQuery->where('created_at', '<=', Carbon::parse($request->date_to));
            }
            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $totalQuery->where(function ($q) use ($searchTerm) {
                    $q->where('message', 'like', '%' . $searchTerm . '%')
                      ->orWhere('context', 'like', '%' . $searchTerm . '%')
                      ->orWhere('properties', 'like', '%' . $searchTerm . '%')
                      ->orWhere('request_id', 'like', '%' . $searchTerm . '%')
                      ->orWhere('controller', 'like', '%' . $searchTerm . '%')
                      ->orWhere('method', 'like', '%' . $searchTerm . '%')
                      ->orWhere('url', 'like', '%' . $searchTerm . '%')
                      ->orWhere('ip_address', 'like', '%' . $searchTerm . '%');
                });
            }
            if ($request->filled('property_key') && $request->filled('property_value')) {
                $totalQuery->whereJsonContains('properties->' . $request->property_key, $request->property_value);
            }
            if ($request->filled('has_property')) {
                $totalQuery->whereNotNull('properties->' . $request->has_property);
            }
            if ($request->filled('property_search')) {
                $totalQuery->where('properties', 'like', '%' . $request->property_search . '%');
            }

            $totalRequests = $totalQuery->select('request_id')->distinct()->get()->count();

            // Get all logs for these request_ids, maintaining the order from $requestIds
            $logs = collect($requestIds)->map(function ($requestId) {
                return LogEntry::where('request_id', $requestId)
                    ->orderBy('created_at', 'asc')
                ->get()
                ->toArray();
            })->toArray();

            return response()->json([
                'logs' => $logs,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $totalRequests,
                    'last_page' => ceil($totalRequests / $perPage),
                    'has_more' => $page < ceil($totalRequests / $perPage),
                ],
                'statistics' => $this->getStatistics($request),
            ]);
        }

        // Order and paginate
        $logs = $query->orderBy('created_at', 'desc')
            ->paginate(config('simple-logging.viewer.per_page', 10));

        return response()->json([
            'logs' => $logs->items(),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'has_more' => $logs->hasMorePages(),
            ],
            'filters' => $request->only([
                'level', 'type', 'date_from', 'date_to', 'search',
                'property_key', 'property_value', 'has_property', 'property_search',
            ]),
        ]);
    }

    /**
     * Get log details
     */
    public function show(LogEntry $log): JsonResponse
    {
        return response()->json([
            'log' => $log,
            'formatted_context' => $log->formatted_context,
            'human_time' => $log->human_time,
            'level_with_emoji' => $log->level_with_emoji,
        ]);
    }

    /**
     * Get logs for a specific request
     */
    public function getRequestLogs(string $requestId): JsonResponse
    {
        $logs = LogEntry::where('request_id', $requestId)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'request_id' => $requestId,
            'logs' => $logs,
            'count' => $logs->count(),
        ]);
    }

    /**
     * Get logs by property value
     */
    public function getLogsByProperty(Request $request, string $key, string $value): JsonResponse
    {
        $logs = LogEntry::forProperty($key, $value)
            ->orderBy('created_at', 'desc')
            ->limit($request->get('limit', 100))
            ->get();

        return response()->json([
            'property_key' => $key,
            'property_value' => $value,
            'logs' => $logs,
            'count' => $logs->count(),
        ]);
    }

    /**
     * Process logs for display - move all logic from Blade to controller
     */
    private function processLogsForDisplay(array $logs): array
    {
        return collect($logs)->map(function ($logsArray) {
            $requestId = $logsArray[0]['request_id'];

            // Find main log (started) and completed log
            $mainLog = collect($logsArray)->first(function ($log) {
                return strpos($log['message'], 'started') !== false;
            }) ?? $logsArray[0];

            // Find the completed log that matches the main method
            $mainMethodName = '';
            if ($mainLog && strpos($mainLog['message'], 'started') !== false) {
                $mainMethodName = str_replace(' started', '', $mainLog['message']);
            }

            $completedLog = collect($logsArray)->first(function ($log) use ($mainMethodName) {
                return strpos($log['message'], 'completed') !== false &&
                       strpos($log['message'], $mainMethodName) !== false;
            });

            $errorLog = collect($logsArray)->first(function ($log) {
                return strpos($log['message'], 'failed') !== false;
            });

            // Calculate status
            $status = $errorLog ? 'error' : ($completedLog ? 'success' : 'info');

            // Calculate total duration from step durations
            $totalDuration = 0;
            foreach ($logsArray as $log) {
                if (isset($log['properties']['duration_ms']) && is_numeric($log['properties']['duration_ms'])) {
                    $totalDuration += $log['properties']['duration_ms'];
                }
            }
            $duration = $totalDuration > 0 ? round($totalDuration, 2) . 'ms' : '-';

            // Extract request info
            $requestInfo = $this->extractRequestInfo($mainLog);

            return [
                'request_id' => $requestId,
                'main_log' => $this->addMicrosecondPrecision($mainLog),
                'status' => $status,
                'duration' => $duration,
                'step_count' => count($logsArray),
                'has_errors' => collect($logsArray)->contains('level', 'error'),
                'has_warnings' => collect($logsArray)->contains('level', 'warning'),
                'request_info' => $requestInfo,
                // Note: steps are loaded on-demand via API call, not included in initial data
            ];
        })->toArray();
    }

    /**
     * Process individual steps for display
     */
    private function processStepsForDisplay(array $logsArray): array
    {
        return collect($logsArray)->sortBy('created_at')->map(function ($log) {
            // Determine category and visual indicator
            $category = $this->determineCategory($log['message']);
            $visualIndicator = 'L' . ($log['call_depth'] ?? 0);

            // Calculate step duration
            $stepDuration = isset($log['properties']['duration_ms']) && is_numeric($log['properties']['duration_ms'])
                ? round($log['properties']['duration_ms'], 2)
                : null;

            // Calculate memory used
            $memoryUsed = isset($log['properties']['memory_used']) && is_numeric($log['properties']['memory_used'])
                ? $log['properties']['memory_used']
                : null;

            // Process data display with correct level and method
            $dataDisplay = $this->processDataDisplay($log, $visualIndicator, $log['message'] ?? 'Unknown');

            // Determine step icon class
            $stepIconClass = $this->determineStepIconClass($log['level']);

            return [
                'log' => $this->addMicrosecondPrecision($log),
                'category' => $category,
                'visual_indicator' => $visualIndicator,
                'step_duration' => $stepDuration,
                'memory_used' => $memoryUsed,
                'data_display' => $dataDisplay,
                'step_icon_class' => $stepIconClass,
                'indent_style' => $this->calculateIndentStyle($log['call_depth'] ?? 0),
            ];
        })->values()->toArray();
    }

    /**
     * Determine log category based on message
     */
    private function determineCategory(string $message): string
    {
        if (strpos($message, 'started') !== false || strpos($message, 'completed') !== false) {
            return 'Function Calls';
        }
        if (strpos($message, 'database') !== false || strpos($message, 'query') !== false) {
            return 'Database';
        }
        if (strpos($message, 'api') !== false || strpos($message, 'http') !== false) {
            return 'API Calls';
        }
        if (strpos($message, 'cache') !== false) {
            return 'Cache';
        }
        if (strpos($message, 'config') !== false || strpos($message, 'setting') !== false) {
            return 'Configuration';
        }

        return 'Actions';
    }

    /**
     * Process data display for a log entry
     */
    private function processDataDisplay(array $log, string $visualIndicator = '', string $methodName = ''): string
    {
        if (! isset($log['properties']) || empty($log['properties'])) {
            return '';
        }

        $properties = $log['properties'];
        $message = $log['message'];

        // Determine relevant keys based on message type
        $relevantKeys = collect($properties)->keys();

        if (strpos($message, 'started') !== false) {
            $relevantKeys = $relevantKeys->filter(function ($key) use ($log) {
                // Remove visual_indicator completely
                if ($key === 'visual_indicator') {
                    return false;
                }

                // Remove category completely
                if ($key === 'category') {
                    return false;
                }

                // Remove request_info completely
                if ($key === 'request_info') {
                    return false;
                }

                // Only show headers in the first step (call_depth = 1)
                if ($key === 'headers' && ($log['call_depth'] ?? 0) !== 1) {
                    return false;
                }

                // Only show user_id if it has a real value
                if ($key === 'user_id') {
                    $value = $log['properties'][$key] ?? null;

                    return $value !== null && $value !== 'N/A' && $value !== '';
                }

                return in_array($key, ['headers', 'user_id', 'input_data', 'session_id', 'request_id']);
            });
        } elseif (strpos($message, 'completed') !== false) {
            $relevantKeys = $relevantKeys->filter(function ($key) use ($log) {
                // Remove visual_indicator completely
                if ($key === 'visual_indicator') {
                    return false;
                }

                // Remove category completely
                if ($key === 'category') {
                    return false;
                }

                // Remove request_info completely
                if ($key === 'request_info') {
                    return false;
                }

                // Only show headers in the first step (call_depth = 1)
                if ($key === 'headers' && ($log['call_depth'] ?? 0) !== 1) {
                    return false;
                }

                return in_array($key, ['duration_ms', 'memory_used', 'headers', 'result']);
            });
        } else {
            $relevantKeys = $relevantKeys->filter(function ($key) use ($log) {
                // Remove visual_indicator completely
                if ($key === 'visual_indicator') {
                    return false;
                }

                // Remove category completely
                if ($key === 'category') {
                    return false;
                }

                // Remove request_info completely
                if ($key === 'request_info') {
                    return false;
                }

                // Only show headers in the first step (call_depth = 1)
                if ($key === 'headers' && ($log['call_depth'] ?? 0) !== 1) {
                    return false;
                }

                return ! in_array($key, ['duration_ms', 'memory_used']);
            });
        }

        if ($relevantKeys->isEmpty()) {
            return '';
        }

        return $relevantKeys->map(function ($key) use ($properties, $visualIndicator, $methodName) {
            $value = $properties[$key] ?? 'N/A';

            if (is_array($value)) {
                $displayValue = '[Array]';
                $jsonValue = json_encode($value);
            } elseif (is_string($value) && strlen($value) > 15) {
                $displayValue = substr($value, 0, 15) . '...';
                $jsonValue = json_encode($value);
            } else {
                $displayValue = $value;
                $jsonValue = json_encode($value);
            }

            $badgeClass = $this->getDataBadgeClass($key);

            // Use the passed visual indicator and method name for the subtitle

            return '<span class="inline-block ' . $badgeClass . ' hover:bg-opacity-80 cursor-pointer px-2 py-1 rounded mr-1 transition-colors text-xs" onclick="showDataValue(\'' . $key . '\', \'' . htmlspecialchars($jsonValue) . '\', \'' . $visualIndicator . '\', \'' . htmlspecialchars($methodName) . '\')" title="Click to view full value">' . $key . ': ' . htmlspecialchars($displayValue) . '</span>';
        })->join('');
    }

    /**
     * Get CSS class for data badge based on key
     */
    private function getDataBadgeClass(string $key): string
    {
        $classes = [
            'duration_ms' => 'bg-green-100 text-green-800',
            'memory_used' => 'bg-purple-100 text-purple-800',
            'headers' => 'bg-blue-100 text-blue-800',
            'user_id' => 'bg-gray-100 text-gray-800',
            'input_data' => 'bg-yellow-100 text-yellow-800',
            'session_id' => 'bg-indigo-100 text-indigo-800',
            'request_id' => 'bg-pink-100 text-pink-800',
            'result' => 'bg-emerald-100 text-emerald-800',
        ];

        return $classes[$key] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Get CSS class for level badge based on log level
     */
    private function getLevelBadgeClass(string $level): string
    {
        $classes = [
            'error' => 'bg-red-100 text-red-800',
            'warning' => 'bg-yellow-100 text-yellow-800',
            'info' => 'bg-blue-100 text-blue-800',
            'debug' => 'bg-gray-100 text-gray-800',
            'success' => 'bg-green-100 text-green-800',
            'critical' => 'bg-red-200 text-red-900',
            'alert' => 'bg-orange-100 text-orange-800',
            'emergency' => 'bg-red-300 text-red-900',
        ];

        return $classes[strtolower($level)] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Get CSS class for status code badge based on HTTP status code
     */
    private function getStatusBadgeClass($statusCode): string
    {
        if (! is_numeric($statusCode)) {
            return 'bg-gray-100 text-gray-800';
        }

        $code = (int) $statusCode;

        if ($code >= 200 && $code < 300) {
            return 'bg-green-100 text-green-800'; // 2xx Success
        } elseif ($code >= 300 && $code < 400) {
            return 'bg-blue-100 text-blue-800'; // 3xx Redirection
        } elseif ($code >= 400 && $code < 500) {
            return 'bg-yellow-100 text-yellow-800'; // 4xx Client Error
        } elseif ($code >= 500) {
            return 'bg-red-100 text-red-800'; // 5xx Server Error
        } else {
            return 'bg-gray-100 text-gray-800'; // Other codes
        }
    }

    /**
     * Determine step icon class based on level
     */
    private function determineStepIconClass(string $level): string
    {
        return match($level) {
            'error' => 'error',
            'warning' => 'warning',
            'debug' => 'debug',
            default => 'info',
        };
    }

    /**
     * Calculate indent style for call depth
     */
    private function calculateIndentStyle(int $depth): string
    {
        return $depth > 0 ? "margin-left: " . ($depth - 1) . "rem;" : "";
    }

    /**
     * Extract request information from main log
     */
    private function extractRequestInfo(array $mainLog): array
    {
        $properties = $mainLog['properties'] ?? [];
        $requestInfo = $properties['requestInfo'] ?? [];

        // Extract headers from properties
        $headers = $properties['headers'] ?? [];
        if (is_string($headers)) {
            $headers = json_decode($headers, true) ?? [];
        }

        return [
            'controller' => $mainLog['controller'] ?? 'Unknown',
            'method' => $mainLog['method'] ?? 'Unknown',
            'http_method' => $requestInfo['method'] ?? $mainLog['http_method'] ?? 'UNKNOWN',
            'url' => $requestInfo['url'] ?? $mainLog['url'] ?? 'Unknown',
            'ip_address' => $requestInfo['ip'] ?? $mainLog['ip_address'] ?? 'Unknown',
            'user_agent' => $requestInfo['user_agent'] ?? $mainLog['user_agent'] ?? 'Unknown',
            'headers' => $headers,
            'request_time' => $mainLog['created_at'] ?? 'Unknown',
            'duration' => $properties['duration_ms'] ?? 'Unknown',
        ];
    }

    /**
     * Add microsecond precision to timestamps
     */
    private function addMicrosecondPrecision(array $log): array
    {
        if (isset($log['created_at'])) {
            $carbon = \Carbon\Carbon::parse($log['created_at']);
            $log['created_at'] = $carbon->format('Y-m-d H:i:s.u');
            $log['created_at_formatted'] = $carbon->format('g:i:s.u A');
            $log['created_at_timestamp'] = $carbon->timestamp;
            $log['created_at_microseconds'] = $carbon->micro;
        }

        return $log;
    }

    /**
     * Get log statistics
     */
    public function getStatistics(Request $request): array
    {
        $days = $request->get('days', 7);
        $stats = LogEntry::getStatistics($days);

        // Transform the statistics to match what the view expects
        return [
            'total_requests' => $stats['total_logs'] ?? 0,
            'error_count' => $stats['by_level']['error'] ?? 0,
            'warning_count' => $stats['by_level']['warning'] ?? 0,
            'info_count' => $stats['by_level']['info'] ?? 0,
            'debug_count' => $stats['by_level']['debug'] ?? 0,
        ];
    }

    /**
     * Export logs
     */
    public function export(Request $request)
    {
        $query = LogEntry::query();

        // Apply same filters as getLogs

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', Carbon::parse($request->date_from));
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', Carbon::parse($request->date_to));
        }

        $format = $request->get('format', 'json');
        $limit = min($request->get('limit', 1000), config('simple-logging.export.max_records', 5000));

        $logs = $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        switch ($format) {
            case 'csv':
                return $this->exportToCsv($logs);
            case 'xlsx':
                return $this->exportToXlsx($logs);
            default:
                return $this->exportToJson($logs);
        }
    }

    /**
     * Clear old logs
     */
    public function clearOldLogs(Request $request): JsonResponse
    {
        $days = $request->get('days', config('simple-logging.cleanup_old_logs_days', 30));
        $deleted = LogEntry::where('created_at', '<', Carbon::now()->subDays($days))->delete();

        return response()->json([
            'message' => "Cleared {$deleted} old log entries",
            'deleted_count' => $deleted,
        ]);
    }

    /**
     * Get available log types
     */
    public function getLogTypes(): JsonResponse
    {
        $types = LogEntry::selectRaw('JSON_EXTRACT(context, "$.type") as type')
            ->whereNotNull('context')
            ->whereNotNull(DB::raw('JSON_EXTRACT(context, "$.type")'))
            ->distinct()
            ->pluck('type')
            ->filter()
            ->values();

        return response()->json($types);
    }

    /**
     * Get available property keys for filtering
     */
    public function getPropertyKeys(): JsonResponse
    {
        $keys = LogEntry::whereNotNull('properties')
            ->get()
            ->pluck('properties')
            ->map(function ($properties) {
                return array_keys($properties);
            })
            ->flatten()
            ->unique()
            ->filter()
            ->values();

        return response()->json($keys);
    }

    /**
     * Get available values for a specific property key
     */
    public function getPropertyValues(Request $request): JsonResponse
    {
        $key = $request->get('key');
        if (! $key) {
            return response()->json([]);
        }

        $values = LogEntry::selectRaw("JSON_EXTRACT(properties, '$.{$key}') as value")
            ->whereNotNull('properties')
            ->whereNotNull(DB::raw("JSON_EXTRACT(properties, '$.{$key}')"))
            ->distinct()
            ->pluck('value')
            ->filter()
            ->values();

        return response()->json($values);
    }

    /**
     * Export to JSON
     */
    private function exportToJson($logs)
    {
        return response()->json([
            'exported_at' => Carbon::now(),
            'count' => $logs->count(),
            'logs' => $logs,
        ])->header('Content-Disposition', 'attachment; filename="logs_' . Carbon::now()->format('Y-m-d_H-i-s') . '.json"');
    }

    /**
     * Export to CSV
     */
    private function exportToCsv($logs)
    {
        $filename = 'logs_' . Carbon::now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');

            // Headers
            fputcsv($file, [
                'ID', 'Request ID', 'Level', 'Message', 'Controller', 'Method',
                'IP Address', 'User Agent', 'URL', 'HTTP Method', 'Status Code', 'Created At',
            ]);

            // Data
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->request_id,
                    $log->level,
                    $log->message,
                    $log->controller,
                    $log->method,
                    $log->ip_address,
                    $log->user_agent,
                    $log->url,
                    $log->http_method,
                    $log->status_code,
                    $log->created_at,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to XLSX (requires maatwebsite/excel package)
     */
    private function exportToXlsx($logs)
    {
        // This would require the maatwebsite/excel package
        // For now, fallback to CSV
        return $this->exportToCsv($logs);
    }

    /**
     * Process detailed log data for a specific request
     */
    private function processDetailedLogData(array $logs): array
    {
        $requestId = $logs[0]['request_id'];

        // Find main log (started) and completed log
        $mainLog = collect($logs)->first(function ($log) {
            return strpos($log['message'], 'started') !== false;
        }) ?? $logs[0];

        // Find the completed log that matches the main method
        $mainMethodName = '';
        if ($mainLog && strpos($mainLog['message'], 'started') !== false) {
            $mainMethodName = str_replace(' started', '', $mainLog['message']);
        }

        $completedLog = collect($logs)->first(function ($log) use ($mainMethodName) {
            return strpos($log['message'], 'completed') !== false &&
                   strpos($log['message'], $mainMethodName) !== false;
        });

        $errorLog = collect($logs)->first(function ($log) {
            return strpos($log['message'], 'failed') !== false;
        });

        // Calculate status
        $status = $errorLog ? 'error' : ($completedLog ? 'success' : 'info');

        // Calculate total duration from step durations
        $totalDuration = 0;
        foreach ($logs as $log) {
            if (isset($log['properties']['duration_ms']) && is_numeric($log['properties']['duration_ms'])) {
                $totalDuration += $log['properties']['duration_ms'];
            }
        }
        $duration = $totalDuration > 0 ? round($totalDuration, 2) . 'ms' : '-';

        // Process steps for display
        $steps = $this->processStepsForDisplay($logs);

        // Extract request info
        $requestInfo = $this->extractRequestInfo($mainLog);

        // Get response data from all completed logs
        $allResponses = $this->extractAllResponses($logs);
        $mainResponseData = $this->extractResponseData($completedLog);

        return [
            'request_id' => $requestId,
            'requestId' => $requestId, // Add this for the view
            'main_log' => $this->addMicrosecondPrecision($mainLog),
            'status' => $status,
            'duration' => $duration,
            'step_count' => count($logs),
            'has_errors' => collect($logs)->contains('level', 'error'),
            'has_warnings' => collect($logs)->contains('level', 'warning'),
            'request_info' => $requestInfo,
            'steps' => $steps,
            'response_data' => $mainResponseData,
            'all_responses' => $allResponses,
            'badge_classes' => [
                'level' => $this->getLevelBadgeClass('info'), // Default level class
                'status' => $this->getStatusBadgeClass($mainResponseData['status_code'] ?? 'N/A'),
            ],
        ];
    }

    /**
     * Extract all responses from all completed logs
     */
    private function extractAllResponses(array $logs): array
    {
        $responses = [];

        foreach ($logs as $log) {
            if (strpos($log['message'], 'completed') !== false) {
                $methodName = str_replace(' completed', '', $log['message']);
                $responseData = $log['properties']['response_data'] ?? null;

                if ($responseData !== null) {
                    $responses[] = [
                        'method_name' => $methodName,
                        'response_data' => $responseData,
                        'duration_ms' => $log['properties']['duration_ms'] ?? null,
                        'memory_used' => $log['properties']['memory_used'] ?? null,
                        'timestamp' => $log['created_at'] ?? null,
                        'level' => $log['level'] ?? 'info',
                    ];
                }
            }
        }

        return $responses;
    }

    /**
     * Extract response data from completed log
     */
    private function extractResponseData($completedLog): array
    {
        if (! $completedLog) {
            return [];
        }

        // Extract response headers from properties
        $responseHeaders = $completedLog['properties']['response_headers'] ?? [];
        if (is_string($responseHeaders)) {
            $responseHeaders = json_decode($responseHeaders, true) ?? [];
        }

        // Get response data from the completed log
        $responseData = $completedLog['properties']['response_data'] ?? [];

        // Extract status code and content type from response_data if available
        $statusCode = $responseData['status_code'] ?? $completedLog['properties']['status_code'] ?? 'N/A';
        $contentType = $responseData['content_type'] ?? $completedLog['properties']['content_type'] ?? 'N/A';

        // Remove status_code and content_type from response_data to avoid duplication
        if (isset($responseData['status_code'])) {
            unset($responseData['status_code']);
        }
        if (isset($responseData['content_type'])) {
            unset($responseData['content_type']);
        }

        return [
            'status_code' => $statusCode,
            'memory_used' => $completedLog['properties']['memory_used'] ?? 'N/A',
            'content_type' => $contentType,
            'response_data' => $responseData,
            'response_headers' => $responseHeaders,
        ];
    }
}
