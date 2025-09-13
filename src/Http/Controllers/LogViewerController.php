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

        return view('simple-logging::index', compact('levels'));
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
            $query->where(function ($q) use ($request) {
                $q->where('message', 'like', '%' . $request->search . '%')
                  ->orWhere('context', 'like', '%' . $request->search . '%')
                  ->orWhere('properties', 'like', '%' . $request->search . '%');
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
            $perPage = $request->get('per_page', config('logging_trait.viewer.per_page', 50));
            $page = $request->get('page', 1);
            $offset = ($page - 1) * $perPage;

            // Get unique request_ids first, then get all logs for those requests
            $requestIds = $query->select('request_id')
                ->distinct()
                ->orderBy('created_at', 'desc')
                ->offset($offset)
                ->limit($perPage)
                ->pluck('request_id');

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
                $totalQuery->where(function ($q) use ($request) {
                    $q->where('message', 'like', '%' . $request->search . '%')
                      ->orWhere('context', 'like', '%' . $request->search . '%')
                      ->orWhere('properties', 'like', '%' . $request->search . '%');
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

            // Get all logs for these request_ids
            $logs = LogEntry::whereIn('request_id', $requestIds)
                ->orderBy('created_at', 'desc')
                ->get()
                ->groupBy('request_id')
                ->map(function ($group) {
                    return $group->toArray();
                })
                ->values()
                ->toArray();

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
            ->paginate(config('logging_trait.viewer.per_page', 50));

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
     * Get log statistics
     */
    public function getStatistics(Request $request): JsonResponse
    {
        $days = $request->get('days', 7);
        $stats = LogEntry::getStatistics($days);

        return response()->json($stats);
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
        $limit = min($request->get('limit', 1000), config('logging_trait.export.max_records', 5000));

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
        $days = $request->get('days', config('logging_trait.cleanup_old_logs_days', 30));
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
}
