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

            // Debug: Log the processed data
            \Log::info('Processed data for request ' . $requestId, [
                'steps_count' => count($processedData['steps'] ?? []),
                'has_steps' => ! empty($processedData['steps']),
                'steps' => $processedData['steps'] ?? [],
            ]);

            // Return the full detailed content with all tabs
            $stepsHtml = '';
            if (! empty($processedData['steps'])) {
                foreach ($processedData['steps'] as $step) {
                    $stepsHtml .= '<div class="step-item" style="' . ($step['indent_style'] ?? '') . '">
                        <div class="flex items-start space-x-3">
                            <div class="step-icon ' . ($step['step_icon_class'] ?? 'info') . ' flex-shrink-0">
                                ' . ($step['visual_indicator'] ?? 'L1') . '
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                    <div class="flex items-center flex-wrap gap-2">
                                        <p class="font-medium text-gray-900 text-sm break-words">' . ($step['log']['message'] ?? 'Unknown') . '</p>
                                        <span class="category-badge category-' . strtolower(str_replace(' ', '-', $step['category'] ?? 'Actions')) . '">' . ($step['category'] ?? 'Actions') . '</span>
                                        ' . (($step['log']['call_depth'] ?? 0) > 0 ? '<span class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded">Level ' . $step['log']['call_depth'] . '</span>' : '') . '
                                    </div>
                                    <div class="flex items-center space-x-2 text-xs text-gray-500 flex-wrap">
                                        ' . (($step['step_duration'] ?? '') ? '<span class="font-mono">' . $step['step_duration'] . 'ms</span>' : '') . '
                                        <span class="text-gray-400">' . \Carbon\Carbon::parse($step['log']['created_at'] ?? now())->format('H:i:s') . '</span>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            ' . ($step['log']['level'] ?? 'info') . '
                                        </span>
                                    </div>
                                </div>
                                ' . (($step['data_display'] ?? '') ? '<div class="mt-1 text-xs text-gray-600">' . $step['data_display'] . '</div>' : '') . '
                            </div>
                        </div>
                    </div>';
                }
            }

            // Generate Request tab content
            $requestInfo = $processedData['request_info'] ?? [];
            $requestHtml = '<div class="space-y-6">
                <!-- Request Information Card -->
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-4 py-3 border-b border-gray-200">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-arrow-right text-blue-600 text-lg"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-lg font-semibold text-gray-900">Request Information</h3>
                                <p class="text-sm text-gray-600">HTTP request details and metadata</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                                        <i class="fas fa-globe mr-2 text-blue-500"></i>
                                        Network Details
                                    </h4>
                                    <div class="space-y-2">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600">HTTP Method</span>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">' . ($requestInfo['http_method'] ?? 'Unknown') . '</span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600">IP Address</span>
                                            <span class="font-mono text-sm text-gray-900">' . ($requestInfo['ip_address'] ?? 'Unknown') . '</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                                        <i class="fas fa-code mr-2 text-purple-500"></i>
                                        Controller Details
                                    </h4>
                                    <div class="space-y-2">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600">Controller</span>
                                            <span class="font-mono text-sm text-gray-900">' . ($requestInfo['controller'] ?? 'Unknown') . '</span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600">Method</span>
                                            <span class="font-mono text-sm text-gray-900">' . ($requestInfo['method'] ?? 'Unknown') . '</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                                        <i class="fas fa-link mr-2 text-indigo-500"></i>
                                        URL Details
                                    </h4>
                                    <div class="space-y-2">
                                        <div>
                                            <span class="text-sm text-gray-600 block mb-1">Full URL</span>
                                            <div class="bg-white border border-gray-200 rounded p-2">
                                                <code class="text-xs text-gray-800 break-all">' . ($requestInfo['url'] ?? 'Unknown') . '</code>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                                        <i class="fas fa-user-agent mr-2 text-orange-500"></i>
                                        User Agent
                                    </h4>
                                    <div class="bg-white border border-gray-200 rounded p-2">
                                        <code class="text-xs text-gray-800 break-all">' . ($requestInfo['user_agent'] ?? 'Unknown') . '</code>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>';

            // Generate Response tab content
            $responseData = $processedData['response_data'] ?? [];
            $responseHtml = '<div class="space-y-6">
                <!-- Response Information Card -->
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 px-4 py-3 border-b border-gray-200">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-arrow-left text-green-600 text-lg"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-lg font-semibold text-gray-900">Response Information</h3>
                                <p class="text-sm text-gray-600">Server response details and data</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="space-y-4">
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                                        <i class="fas fa-check-circle mr-2 text-green-500"></i>
                                        Status Details
                                    </h4>
                                    <div class="space-y-2">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600">Status Code</span>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">' . ($responseData['status_code'] ?? 'N/A') . '</span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600">Content Type</span>
                                            <span class="font-mono text-sm text-gray-900">' . ($responseData['content_type'] ?? 'N/A') . '</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                                        <i class="fas fa-memory mr-2 text-purple-500"></i>
                                        Performance
                                    </h4>
                                    <div class="space-y-2">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600">Memory Used</span>
                                            <span class="font-mono text-sm text-gray-900">' . ($responseData['memory_used'] ?? 'N/A') . '</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                                        <i class="fas fa-database mr-2 text-blue-500"></i>
                                        Response Data
                                    </h4>
                                    <div class="bg-gray-900 rounded-lg p-4 overflow-auto max-h-64">
                                        <pre class="text-green-400 text-sm font-mono whitespace-pre-wrap break-words">' . json_encode($responseData['data'] ?? [], JSON_PRETTY_PRINT) . '</pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>';

            // Generate Headers tab content
            $headersData = $processedData['request_info']['headers'] ?? [];
            $headersHtml = '<div class="space-y-6">
                <!-- Headers Information Card -->
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-50 to-pink-50 px-4 py-3 border-b border-gray-200">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-headers text-purple-600 text-lg"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-lg font-semibold text-gray-900">Request Headers</h3>
                                <p class="text-sm text-gray-600">HTTP headers sent with the request</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="bg-gray-50 rounded-lg p-3">
                            <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                                <i class="fas fa-list mr-2 text-purple-500"></i>
                                Header Details
                            </h4>
                            <div class="bg-gray-900 rounded-lg p-4 overflow-auto max-h-64">
                                <pre class="text-green-400 text-sm font-mono whitespace-pre-wrap break-words">' . json_encode($headersData, JSON_PRETTY_PRINT) . '</pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>';

            return response('<div class="space-y-3">
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600 font-medium">Controller:</span>
                            <div class="text-gray-900 font-mono">' . ($processedData['main_log']['controller'] ?? 'Unknown') . '</div>
                        </div>
                        <div>
                            <span class="text-gray-600 font-medium">Method:</span>
                            <div class="text-gray-900 font-mono">' . ($processedData['main_log']['method'] ?? 'Unknown') . '</div>
                        </div>
                        <div>
                            <span class="text-gray-600 font-medium">Duration:</span>
                            <div class="text-gray-900 font-mono">' . ($processedData['duration'] ?? 'Unknown') . '</div>
                        </div>
                        <div>
                            <span class="text-gray-600 font-medium">Steps:</span>
                            <div class="text-gray-900 font-mono">' . count($processedData['steps'] ?? []) . '</div>
                        </div>
                    </div>
                </div>
                <div class="border-b border-gray-200 pb-2">
                    <nav class="flex space-x-1">
                        <button class="tab-button active" data-tab="steps-' . $requestId . '">
                            <i class="fas fa-list mr-1"></i>Steps
                        </button>
                        <button class="tab-button" data-tab="request-' . $requestId . '">
                            <i class="fas fa-arrow-right mr-1"></i>Request
                        </button>
                        <button class="tab-button" data-tab="response-' . $requestId . '">
                            <i class="fas fa-arrow-left mr-1"></i>Response
                        </button>
                        <button class="tab-button" data-tab="headers-' . $requestId . '">
                            <i class="fas fa-headers mr-1"></i>Headers
                        </button>
                    </nav>
                </div>
                <div class="tab-content active" id="steps-' . $requestId . '">
                    <div class="space-y-2">
                        ' . ($stepsHtml ?: '<div class="text-center py-4 text-gray-500">No steps available</div>') . '
                    </div>
                </div>
                <div class="tab-content" id="request-' . $requestId . '">
                    ' . $requestHtml . '
                </div>
                <div class="tab-content" id="response-' . $requestId . '">
                    ' . $responseHtml . '
                </div>
                <div class="tab-content" id="headers-' . $requestId . '">
                    ' . $headersHtml . '
                </div>
            </div>
            <script>
                // Tab switching for this specific request
                document.addEventListener("DOMContentLoaded", function() {
                    const requestId = "' . $requestId . '";
                    const cardContainer = document.querySelector(\'[data-request-id="\' + requestId + \'"]\');
                    if (cardContainer) {
                        cardContainer.addEventListener("click", function(e) {
                            if (e.target.classList.contains("tab-button") || e.target.closest(".tab-button")) {
                                e.preventDefault();
                                e.stopPropagation();
                                
                                const tabButton = e.target.classList.contains("tab-button") ? e.target : e.target.closest(".tab-button");
                                const tabId = tabButton.getAttribute("data-tab");
                                
                                // Remove active class from all tabs in this card only
                                const allTabs = cardContainer.querySelectorAll(".tab-button");
                                allTabs.forEach(tab => tab.classList.remove("active"));
                                
                                // Hide all tab content in this card only
                                const allContent = cardContainer.querySelectorAll(".tab-content");
                                allContent.forEach(content => content.classList.remove("active"));
                                
                                // Activate clicked tab
                                tabButton.classList.add("active");
                                const targetContent = cardContainer.querySelector("#" + tabId);
                                if (targetContent) {
                                    targetContent.classList.add("active");
                                }
                            }
                        });
                    }
                });
                
                // Data modal functions
                function showDataValue(key, value, level = "", method = "") {
                    try {
                        const parsedValue = JSON.parse(value);
                        const title = key === "headers" ? `View <span style="color: #3b82f6;">${key}</span>` : `View <span style="color: #3b82f6;">${key}</span>`;
                        const subtitle = level && method ? `From: ${level} ${method}` : `Data value for: ${key}`;
                        showDataModal(title, parsedValue, subtitle);
                    } catch (e) {
                        const title = key === "headers" ? `View <span style="color: #3b82f6;">${key}</span>` : `View <span style="color: #3b82f6;">${key}</span>`;
                        const subtitle = level && method ? `From: ${level} ${method}` : `Data value for: ${key}`;
                        showDataModal(title, value, subtitle);
                    }
                }
                
                function showDataModal(title, data, message) {
                    // Create modal if it doesn\'t exist
                    let modal = document.getElementById("data-modal");
                    if (!modal) {
                        modal = document.createElement("div");
                        modal.id = "data-modal";
                        modal.className = "fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden";
                        modal.innerHTML = `
                            <div class="bg-white rounded-lg shadow-xl max-w-4xl max-h-[80vh] w-full mx-4">
                                <div class="flex items-center justify-between p-4 border-b border-gray-200 pb-2">
                                    <h3 class="text-lg font-semibold text-gray-900" id="modal-title">Data Viewer</h3>
                                    <button onclick="closeDataModal()" class="text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-times text-xl"></i>
                                    </button>
                                </div>
                                <div class="p-4">
                                    <div class="mb-3">
                                        <span class="text-sm text-gray-600">From:</span>
                                        <span class="text-sm font-medium text-gray-900" id="modal-message"></span>
                                    </div>
                                    <div class="bg-gray-900 rounded-lg p-4 overflow-auto max-h-96">
                                        <pre class="text-green-400 text-sm font-mono" id="modal-content"></pre>
                                    </div>
                                </div>
                                <div class="flex justify-end p-4 border-t border-gray-200">
                                    <button onclick="closeDataModal()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                                        Close
                                    </button>
                                </div>
                            </div>
                        `;
                        document.body.appendChild(modal);
                    }
                    
                    // Update modal content
                    document.getElementById("modal-title").innerHTML = title;
                    document.getElementById("modal-message").textContent = message;
                    document.getElementById("modal-content").textContent = JSON.stringify(data, null, 2);
                    
                    // Show modal
                    modal.classList.remove("hidden");
                }
                
                function closeDataModal() {
                    const modal = document.getElementById("data-modal");
                    if (modal) {
                        modal.classList.add("hidden");
                    }
                }
                
                // Close modal when clicking outside
                document.addEventListener("click", (e) => {
                    const modal = document.getElementById("data-modal");
                    if (e.target === modal) {
                        closeDataModal();
                    }
                });
            </script>', 200, ['Content-Type' => 'text/html']);

        } catch (\Exception $e) {
            \Log::error('Error in getLogDetails: ' . $e->getMessage(), [
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

        return [
            'controller' => $mainLog['controller'] ?? 'Unknown',
            'method' => $mainLog['method'] ?? 'Unknown',
            'http_method' => $requestInfo['method'] ?? $mainLog['http_method'] ?? 'UNKNOWN',
            'url' => $requestInfo['url'] ?? $mainLog['url'] ?? 'Unknown',
            'ip_address' => $requestInfo['ip'] ?? $mainLog['ip_address'] ?? 'Unknown',
            'user_agent' => $requestInfo['user_agent'] ?? $mainLog['user_agent'] ?? 'Unknown',
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

        // Get response data
        $responseData = $this->extractResponseData($completedLog);

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
            'response_data' => $responseData,
        ];
    }

    /**
     * Extract response data from completed log
     */
    private function extractResponseData($completedLog): array
    {
        if (! $completedLog) {
            return [];
        }

        return [
            'status_code' => $completedLog['properties']['status_code'] ?? 'N/A',
            'memory_used' => $completedLog['properties']['memory_used'] ?? 'N/A',
            'content_type' => $completedLog['properties']['content_type'] ?? 'N/A',
            'data' => $completedLog['properties']['response_data'] ?? [],
        ];
    }
}
