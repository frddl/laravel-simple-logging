<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Viewer</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .log-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 8px;
            transition: all 0.2s ease;
            border: 1px solid #e5e7eb;
            overflow: visible;
            position: relative;
            z-index: 1;
        }
        .log-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-1px);
        }
        .log-header {
            padding: 12px 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
        }
        .log-content {
            display: none !important;
            padding: 16px;
            border-top: 1px solid #f3f4f6;
            max-height: 0 !important;
            overflow: hidden !important;
            opacity: 0 !important;
            visibility: hidden !important;
            word-wrap: break-word;
            max-width: 100%;
            box-sizing: border-box;
        }
        .log-content.show {
            display: block !important;
            max-height: none !important;
            overflow: visible !important;
            opacity: 1 !important;
            visibility: visible !important;
            position: relative !important;
            z-index: 1 !important;
        }
        .log-content .tab-content {
            display: none !important;
        }
        .log-content.show .tab-content.active {
            display: block !important;
        }
        .log-card .log-content {
            display: none !important;
        }
        .log-card .log-content.show {
            display: block !important;
        }
        .log-card .log-content .tab-content {
            display: none !important;
        }
        .log-card .log-content.show .tab-content.active {
            display: block !important;
        }
        .log-card .log-content .step-item {
            display: none !important;
        }
        .log-card .log-content.show .tab-content.active .step-item {
            display: block !important;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-success {
            background: #d1fae5;
            color: #065f46;
        }
        .status-error {
            background: #fee2e2;
            color: #991b1b;
        }
        .status-info {
            background: #dbeafe;
            color: #1e40af;
        }
        .status-warning {
            background: #fef3c7;
            color: #92400e;
        }
        .method-tag {
            background: #f3f4f6;
            color: #374151;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 500;
        }
        .trace-id {
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 10px;
            color: #6b7280;
            background: #f9fafb;
            padding: 2px 6px;
            border-radius: 4px;
        }
        .duration {
            font-size: 11px;
            color: #6b7280;
            font-weight: 500;
        }
        .time {
            font-size: 12px;
            color: #6b7280;
            font-weight: 500;
        }
        .tab-content {
            display: none !important;
        }
        .tab-content.active {
            display: block !important;
        }
        .tab-button {
            transition: all 0.2s ease;
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 6px;
            margin-right: 8px;
        }
        .tab-button.active {
            background-color: #3b82f6;
            color: white;
        }
        .step-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 12px;
            margin-bottom: 6px;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            overflow: visible;
            word-wrap: break-word;
            max-width: 100%;
            box-sizing: border-box;
            position: relative;
            z-index: 2;
        }
        .step-item .flex-1 {
            min-width: 0;
            overflow: hidden;
        }
        .step-item .break-words {
            word-break: break-word;
            overflow-wrap: break-word;
        }
        .step-icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
        }
        .step-icon.success {
            background: #10b981;
            color: white;
        }
        .step-icon.error {
            background: #ef4444;
            color: white;
        }
        .step-icon.info {
            background: #3b82f6;
            color: white;
        }
        .step-icon.warning {
            background: #f59e0b;
            color: white;
        }
        .step-icon.debug {
            background: #6b7280;
            color: white;
        }
        
        /* Visual indicators for log types */
        .log-type-indicator {
            display: inline-flex;
            align-items: center;
            padding: 2px 6px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            margin-right: 6px;
        }
        .log-type-function {
            background: #dbeafe;
            color: #1e40af;
        }
        .log-type-business {
            background: #f3e8ff;
            color: #7c3aed;
        }
        .log-type-database {
            background: #fef3c7;
            color: #d97706;
        }
        .log-type-api {
            background: #ecfdf5;
            color: #059669;
        }
        .log-type-config {
            background: #fce7f3;
            color: #be185d;
        }
        .log-type-action {
            background: #f1f5f9;
            color: #475569;
        }
        
        /* Category badges */
        .category-badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 500;
            margin-left: 4px;
        }
        .category-function-calls {
            background: #e0f2fe;
            color: #0277bd;
        }
        .category-business-logic {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        .category-database-operations {
            background: #fff3e0;
            color: #ef6c00;
        }
        .category-api-operations {
            background: #e8f5e8;
            color: #2e7d32;
        }
        .category-configuration {
            background: #fce4ec;
            color: #c2185b;
        }
        .category-actions {
            background: #f5f5f5;
            color: #424242;
        }
        
        /* HTTP method indicators */
        .http-method {
            display: inline-flex;
            align-items: center;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            margin-right: 6px;
        }
        .http-get {
            background: #dcfce7;
            color: #166534;
        }
        .http-post {
            background: #dbeafe;
            color: #1e40af;
        }
        .http-put {
            background: #fef3c7;
            color: #92400e;
        }
        .http-delete {
            background: #fee2e2;
            color: #991b1b;
        }
        .http-patch {
            background: #f3e8ff;
            color: #7c3aed;
        }
        .json-viewer {
            background: #1f2937;
            color: #f9fafb;
            border-radius: 8px;
            padding: 12px;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 11px;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .json-viewer pre {
            margin: 0;
            white-space: pre-wrap;
            word-wrap: break-word;
            overflow-x: auto;
            max-height: 100%;
        }
        
        /* Mobile-specific styles */
        @media (max-width: 640px) {
            .log-card {
                margin-bottom: 12px;
                border-radius: 8px;
            }
            .log-header {
                padding: 12px 16px;
            }
            .log-content {
                padding: 0 16px 16px 16px;
            }
            .step-item {
                padding: 8px 12px;
                margin-bottom: 6px;
                border-radius: 6px;
            }
            .tab-button {
                padding: 6px 12px;
                font-size: 12px;
                margin-right: 6px;
                border-radius: 4px;
            }
            .status-badge {
                padding: 4px 8px;
                font-size: 11px;
                border-radius: 12px;
            }
            .time {
                font-size: 13px;
                font-weight: 600;
            }
        }

        /* Mobile drawer styles */
        @media (max-width: 1023px) {
            #filters-sidebar {
                width: 100%;
                max-width: 320px;
                box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            }
            
            #mobile-menu-toggle {
                transition: all 0.2s ease;
            }
            
            #mobile-menu-toggle:hover {
                transform: scale(1.05);
            }
            
            /* Ensure drawer slides in from left */
            #filters-sidebar.show {
                transform: translateX(0);
            }
        }

        /* Pagination button styles */
        .pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background-color: #f3f4f6 !important;
            color: #9ca3af !important;
            border-color: #e5e7eb !important;
        }
        
        .pagination-btn:disabled:hover {
            background-color: #f3f4f6 !important;
            transform: none !important;
        }
    </style>
</head>
<body class="bg-gray-50 h-screen overflow-hidden">
    <div class="h-full">

        <!-- Main Content Area -->
        <div class="flex flex-col lg:flex-row h-screen">
            <!-- Mobile Overlay -->
            <div id="mobile-overlay" class="lg:hidden fixed inset-0 bg-black bg-opacity-50 z-30 hidden"></div>

            <!-- Left Sidebar - Filters -->
            <div id="filters-sidebar" class="w-full lg:w-80 bg-white shadow-lg border-r border-gray-200 flex flex-col transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out fixed lg:relative z-40 h-full left-0 top-0">
                <!-- Sidebar Header -->
                <div class="p-4 lg:p-6 border-b border-gray-200 pb-2">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-base lg:text-lg font-bold text-gray-900 mb-2">
                                <i class="fas fa-filter mr-2 text-blue-600"></i>
                                <span class="hidden sm:inline">Filters & Controls</span>
                                <span class="sm:hidden">Filters</span>
                            </h2>
                            <p class="text-sm text-gray-600">Refine your log search</p>
                        </div>
                        <!-- Mobile Close Button -->
                        <button id="mobile-menu-close" class="lg:hidden text-gray-400 hover:text-gray-600 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>

                <!-- Filters -->
                <div class="flex-1 overflow-y-auto p-4 lg:p-6 space-y-4 lg:space-y-6">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-search mr-1"></i>Search
                        </label>
                        <div class="relative">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            <input type="text" id="search" placeholder="Logs, methods, or trace IDs..." class="w-full pl-10 pr-4 py-2 lg:py-3 border-2 border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                        </div>
                    </div>
                    
                    <!-- Level Filter -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-layer-group mr-1"></i>Level
                        </label>
                        <select id="level-filter" class="w-full border-2 border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                            <option value="">All Levels</option>
                            @foreach($levels as $level)
                                <option value="{{ $level }}">{{ ucfirst($level) }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Date Range -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-calendar-alt mr-1"></i>Date Range
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">From</label>
                                <input type="datetime-local" id="date-from" class="w-full border-2 border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">To</label>
                                <input type="datetime-local" id="date-to" class="w-full border-2 border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Per Page -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-list mr-1"></i>Per Page
                        </label>
                        <select id="per-page" class="w-full border-2 border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                            <option value="10">10</option>
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    
                    
                    <!-- Info -->
                    <div class="text-xs text-gray-500 text-center">
                        <i class="fas fa-info-circle mr-1"></i>
                        Filters apply automatically on change
                    </div>
                </div>

                <!-- Compact Statistics -->
                <div class="p-4 border-t border-gray-200 bg-gray-50">
                    <div class="flex justify-between gap-2">
                        <div class="flex-1 text-center">
                            <div class="text-lg font-bold text-blue-600" id="total-logs">{{ $statistics['total_requests'] ?? 0 }}</div>
                            <div class="text-xs text-gray-600">Traces</div>
                        </div>
                        <div class="flex-1 text-center">
                            <div class="text-lg font-bold text-green-600" id="success-logs">{{ $statistics['info_count'] ?? 0 }}</div>
                            <div class="text-xs text-gray-600">Success</div>
                        </div>
                        <div class="flex-1 text-center">
                            <div class="text-lg font-bold text-yellow-600" id="warning-logs">{{ $statistics['warning_count'] ?? 0 }}</div>
                            <div class="text-xs text-gray-600">Warnings</div>
                        </div>
                        <div class="flex-1 text-center">
                            <div class="text-lg font-bold text-red-600" id="error-logs">{{ $statistics['error_count'] ?? 0 }}</div>
                            <div class="text-xs text-gray-600">Errors</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Content - Main Table View -->
            <div class="flex-1 flex flex-col bg-gray-50 min-h-0">
                <!-- Main Header -->
                <div class="bg-white shadow-sm border-b border-gray-200 pb-2 px-4 lg:px-6 py-4 pt-4 lg:pt-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center">
                            <h2 class="text-lg lg:text-xl font-bold text-gray-900">
                                <i class="fas fa-stream mr-2 text-blue-600"></i>
                                <span class="inline">Request Traces</span>
                            </h2>
                            <!-- Mobile Filter Button -->
                            <button id="mobile-filter-toggle" class="sm:hidden ml-3 p-2 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                <i class="fas fa-filter"></i>
                            </button>
                        </div>
                        <div class="mt-2">
                            <p class="text-sm text-gray-600">Click on any trace to view detailed execution steps and data</p>
                        </div>
                    </div>
                </div>

                <!-- Logs Container -->
                <div class="flex-1 overflow-y-auto p-4 lg:p-6">
                    <div id="logs-container" class="space-y-3 lg:space-y-4">
                        @if(isset($groupedLogs['logs']) && count($groupedLogs['logs']) > 0)
                            @foreach($groupedLogs['logs'] as $logsArray)
                                @if(empty($logsArray) || !isset($logsArray[0]) || !isset($logsArray[0]['request_id'])) @continue @endif
                                @php
                                    $requestId = $logsArray[0]['request_id'];
                                    $mainLog = collect($logsArray)->first(function($log) {
                                        return strpos($log['message'], 'started') !== false;
                                    }) ?? $logsArray[0];
                                    
                                    // Find the completed log that matches the main method
                                    $mainMethodName = '';
                                    if ($mainLog && strpos($mainLog['message'], 'started') !== false) {
                                        $mainMethodName = str_replace(' started', '', $mainLog['message']);
                                    }
                                    
                                    $completedLog = collect($logsArray)->first(function($log) use ($mainMethodName) {
                                        return strpos($log['message'], 'completed') !== false && 
                                               strpos($log['message'], $mainMethodName) !== false;
                                    });
                                    $errorLog = collect($logsArray)->first(function($log) {
                                        return strpos($log['message'], 'failed') !== false;
                                    });
                                    
                                    $status = $errorLog ? 'error' : ($completedLog ? 'success' : 'info');
                                    // Calculate total request duration from step durations
                                    $duration = '-';
                                    $totalDuration = 0;
                                    foreach ($logsArray as $log) {
                                        if (isset($log['properties']['duration_ms']) && is_numeric($log['properties']['duration_ms'])) {
                                            $totalDuration += $log['properties']['duration_ms'];
                                        }
                                    }
                                    if ($totalDuration > 0) {
                                        $duration = round($totalDuration, 2) . 'ms';
                                    }
                                    $stepCount = count($logsArray);
                                    $hasErrors = collect($logsArray)->contains('level', 'error');
                                    $hasWarnings = collect($logsArray)->contains('level', 'warning');
                                    
                                    $statusClasses = [
                                        'success' => 'status-success',
                                        'error' => 'status-error',
                                        'info' => 'status-info',
                                        'warning' => 'status-warning'
                                    ];
                                    
                                    $controllerName = $mainLog['controller'] ?? 'Unknown';
                                    $methodName = $mainLog['method'] ?? 'Unknown';
                                    $requestInfo = $mainLog['properties']['request_info'] ?? [];
                                    $httpMethod = $requestInfo['method'] ?? 'UNKNOWN';
                                @endphp
                                
                                <div class="log-card" data-request-id="{{ $requestId }}">
                                    <div class="log-header" data-request-id="{{ $requestId }}">
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between w-full gap-2">
                                            <div class="flex flex-col sm:flex-row sm:items-center space-y-2 sm:space-y-0 sm:space-x-4">
                                                <div class="flex items-center space-x-2">
                                                    <span class="time text-sm font-medium text-gray-600">{{ \Carbon\Carbon::parse($mainLog['created_at'])->format('g:i:s A') }}</span>
                                                    <span class="status-badge {{ $statusClasses[$status] }}">{{ strtoupper($status) }}</span>
                                                    @if($duration !== '-')
                                                        <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">{{ $duration }}</span>
                                                    @endif
                                                    <span class="http-method http-{{ strtolower($httpMethod) }}">{{ $httpMethod }}</span>
                                                </div>
                                                <div class="flex items-center space-x-2 min-w-0">
                                                    <span class="text-sm font-semibold text-gray-800 truncate">{{ $controllerName }}</span>
                                                    <span class="text-gray-400 flex-shrink-0">::</span>
                                                    <span class="text-sm text-blue-600 font-medium truncate">{{ $methodName }}</span>
                                                </div>
                                                <div class="flex items-center space-x-2 text-gray-500">
                                                    <span class="text-xs">{{ $stepCount }} step{{ $stepCount !== 1 ? 's' : '' }}</span>
                                                    @if($hasErrors)
                                                        <i class="fas fa-exclamation-circle text-red-500 text-xs"></i>
                                                    @endif
                                                    @if($hasWarnings)
                                                        <i class="fas fa-exclamation-triangle text-yellow-500 text-xs"></i>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="flex items-center justify-between sm:justify-end space-x-3">
                                                <div class="text-left sm:text-right">
                                                    <div class="text-xs text-gray-500 font-mono break-all">{{ $requestId }}</div>
                                                </div>
                                                <i class="fas fa-chevron-down text-gray-400 transition-transform duration-200 flex-shrink-0" id="chevron-{{ $requestId }}"></i>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="log-content" id="content-{{ $requestId }}">
                                        <div class="space-y-3">
                                            <!-- Summary Info -->
                                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                                    <div>
                                                        <span class="text-gray-600 font-medium">Controller:</span>
                                                        <div class="text-gray-900 font-mono">{{ $controllerName }}</div>
                                                    </div>
                                                    <div>
                                                        <span class="text-gray-600 font-medium">Method:</span>
                                                        <div class="text-gray-900 font-mono">{{ $methodName }}</div>
                                                    </div>
                                                    <div>
                                                        <span class="text-gray-600 font-medium">Duration:</span>
                                                        <div class="text-gray-900 font-mono">{{ $duration }}ms</div>
                                                    </div>
                                                    <div>
                                                        <span class="text-gray-600 font-medium">Steps:</span>
                                                        <div class="text-gray-900 font-mono">{{ $stepCount }}</div>
                                                    </div>
                                                </div>
                                                <div class="mt-3 pt-3 border-t border-gray-200 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                                    <div>
                                                        <span class="text-gray-600 font-medium">HTTP Method:</span>
                                                        <div class="text-gray-900 font-mono">{{ $httpMethod }}</div>
                                                    </div>
                                                    <div>
                                                        <span class="text-gray-600 font-medium">IP Address:</span>
                                                        <div class="text-gray-900 font-mono">{{ $requestInfo['ip_address'] ?? 'Unknown' }}</div>
                                                    </div>
                                                    <div>
                                                        <span class="text-gray-600 font-medium">URL:</span>
                                                        <div class="text-gray-900 font-mono text-xs break-all">{{ $requestInfo['url'] ?? 'Unknown' }}</div>
                                                    </div>
                                                    <div>
                                                        <span class="text-gray-600 font-medium">User Agent:</span>
                                                        <div class="text-gray-900 font-mono text-xs break-all">{{ $requestInfo['user_agent'] ?? 'Unknown' }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Tabs -->
                                            <div class="border-b border-gray-200">
                                                <nav class="flex space-x-1">
                                                    <button class="tab-button active" data-tab="steps-{{ $requestId }}">
                                                        <i class="fas fa-list mr-1"></i>Steps
                                                    </button>
                                                    <button class="tab-button" data-tab="request-{{ $requestId }}">
                                                        <i class="fas fa-arrow-right mr-1"></i>Request
                                                    </button>
                                                    <button class="tab-button" data-tab="response-{{ $requestId }}">
                                                        <i class="fas fa-arrow-left mr-1"></i>Response
                                                    </button>
                                                    <button class="tab-button" data-tab="headers-{{ $requestId }}">
                                                        <i class="fas fa-headers mr-1"></i>Headers
                                                    </button>
                                                </nav>
                                            </div>

                                            <!-- Tab Content -->
                                            <div class="tab-content active" id="steps-{{ $requestId }}">
                                                <div class="space-y-2" style="max-width: 100%; overflow: visible; box-sizing: border-box; position: relative; z-index: 1;">
                                                    @foreach(collect($logsArray)->sortBy('created_at') as $log)
                                                        @php
                                                            $stepDuration = $log['properties']['duration_ms'] ?? '';
                                                            $memoryUsed = $log['properties']['memory_used'] ?? '';
                                                            $hasData = !empty($log['properties']);
                                                            
                                                            $depth = $log['call_depth'] ?? 0;
                                                            $logType = $log['properties']['log_type'] ?? 'action';
                                                            $visualIndicator = "L{$depth}";
                                                            $category = $log['properties']['category'] ?? 'Actions';
                                                            
                                                            $indentStyle = $depth > 0 ? "margin-left: " . ($depth - 1) . "rem;" : "";
                                                            
                                                            $stepIconClass = 'info';
                                                            if ($log['level'] === 'error' || str_contains($log['message'], 'failed')) {
                                                                $stepIconClass = 'error';
                                                            } elseif ($log['level'] === 'warning' || str_contains($log['message'], 'warning')) {
                                                                $stepIconClass = 'warning';
                                                            } elseif ($log['level'] === 'debug') {
                                                                $stepIconClass = 'debug';
                                                            }
                                                            
                                                            $relevantKeys = collect($log['properties'])
                                                                ->keys()
                                                                ->reject(function($key) {
                                                                    return in_array($key, ['duration_ms', 'memory_used', 'log_type', 'visual_indicator', 'category', 'request_info']);
                                                                })
                                                                ->values();
                                                            
                                                            $dataDisplay = '';
                                                            if ($hasData) {
                                                                if (str_contains($log['message'], 'started')) {
                                                                    $dataDisplay = $relevantKeys->map(function($key) use ($log) {
                                                                        $value = $log['properties'][$key] ?? 'N/A';
                                                                        if (is_array($value)) {
                                                                            $displayValue = '[Array]';
                                                                        } elseif (is_string($value) && strlen($value) > 15) {
                                                                            $displayValue = substr($value, 0, 15) . '...';
                                                                        } else {
                                                                            $displayValue = $value;
                                                                        }
                                                                        return '<span class="inline-block bg-blue-100 hover:bg-blue-200 cursor-pointer px-2 py-1 rounded mr-1 transition-colors text-xs" onclick="showDataValue(\'' . $key . '\', \'' . htmlspecialchars(json_encode($value)) . '\')" title="Click to view full value">' . $key . ': ' . htmlspecialchars($displayValue) . '</span>';
                                                                    })->join('');
                                                                } elseif (str_contains($log['message'], 'completed')) {
                                                                    $completionKeys = collect(['duration_ms', 'memory_used'])->filter(function($key) use ($log) {
                                                                        return isset($log['properties'][$key]);
                                                                    });
                                                                    $otherKeys = $relevantKeys->take(1);
                                                                    $allKeys = $completionKeys->merge($otherKeys)->take(3);
                                                                    $dataDisplay = $allKeys->map(function($key) use ($log) {
                                                                        $value = $log['properties'][$key] ?? 'N/A';
                                                                        if (is_array($value)) {
                                                                            $displayValue = '[Array]';
                                                                        } elseif (is_string($value) && strlen($value) > 15) {
                                                                            $displayValue = substr($value, 0, 15) . '...';
                                                                        } else {
                                                                            $displayValue = $value;
                                                                        }
                                                                        return '<span class="inline-block bg-green-100 hover:bg-green-200 cursor-pointer px-2 py-1 rounded mr-1 transition-colors text-xs" onclick="showDataValue(\'' . $key . '\', \'' . htmlspecialchars(json_encode($value)) . '\')" title="Click to view full value">' . $key . ': ' . htmlspecialchars($displayValue) . '</span>';
                                                                    })->join('');
                                                                } else {
                                                                    $dataDisplay = $relevantKeys->map(function($key) use ($log) {
                                                                        $value = $log['properties'][$key] ?? 'N/A';
                                                                        if (is_array($value)) {
                                                                            $displayValue = '[Array]';
                                                                        } elseif (is_string($value) && strlen($value) > 15) {
                                                                            $displayValue = substr($value, 0, 15) . '...';
                                                                        } else {
                                                                            $displayValue = $value;
                                                                        }
                                                                        return '<span class="inline-block bg-gray-100 hover:bg-gray-200 cursor-pointer px-2 py-1 rounded mr-1 transition-colors text-xs" onclick="showDataValue(\'' . $key . '\', \'' . htmlspecialchars(json_encode($value)) . '\')" title="Click to view full value">' . $key . ': ' . htmlspecialchars($displayValue) . '</span>';
                                                                    })->join('');
                                                                }
                                                            }
                                                        @endphp
                                                        
                                                        <div class="step-item" style="{{ $indentStyle }} max-width: calc(100% - {{ $depth > 0 ? ($depth - 1) * 1 : 0 }}rem); box-sizing: border-box; position: relative; z-index: 2;">
                                                            <div class="flex items-start space-x-3" style="max-width: 100%; box-sizing: border-box; overflow: visible;">
                                                                <div class="step-icon {{ $stepIconClass }} flex-shrink-0">
                                                                    {{ $visualIndicator }}
                                                                </div>
                                                                <div class="flex-1 min-w-0">
                                                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                                                        <div class="flex items-center flex-wrap gap-2">
                                                                            <p class="font-medium text-gray-900 text-sm break-words">{{ $log['message'] }}</p>
                                                                            <span class="category-badge category-{{ strtolower(str_replace(' ', '-', $category)) }}">{{ $category }}</span>
                                                                            @if($depth > 0)
                                                                                <span class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded">Level {{ $depth }}</span>
                                                                            @endif
                                                                        </div>
                                                                        <div class="flex items-center space-x-2 text-xs text-gray-500">
                                                                            <span>{{ \Carbon\Carbon::parse($log['created_at'])->format('g:i:s A') }}</span>
                                                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $stepIconClass === 'error' ? 'bg-red-100 text-red-800' : ($stepIconClass === 'warning' ? 'bg-yellow-100 text-yellow-800' : ($stepIconClass === 'debug' ? 'bg-gray-100 text-gray-800' : 'bg-blue-100 text-blue-800')) }}">
                                                                                {{ $log['level'] }}
                                                                            </span>
                                                                            @if($stepDuration)
                                                                                <span class="inline-block bg-green-100 text-green-800 px-2 py-1 rounded text-xs" title="Duration: {{ $stepDuration }}ms">
                                                                                    {{ $stepDuration }}ms
                                                                                </span>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                    @if($memoryUsed)
                                                                        <div class="mt-1 text-xs text-gray-600">
                                                                            <span class="inline-block bg-purple-100 text-purple-800 px-2 py-1 rounded" title="Memory: {{ number_format($memoryUsed / 1024, 1) }}KB">
                                                                                {{ number_format($memoryUsed / 1024, 1) }}KB
                                                                            </span>
                                                                        </div>
                                                                    @endif
                                                                    @if($hasData && $dataDisplay)
                                                                        <div class="mt-1 text-xs text-gray-600">
                                                                            {!! $dataDisplay !!}
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                            
                                            <!-- Other tabs content would go here -->
                                            <div class="tab-content" id="request-{{ $requestId }}">
                                                <div class="space-y-4">
                                                    <h3 class="text-lg font-medium text-gray-900">Request Information</h3>
                                                    <div class="bg-gray-50 rounded-lg p-4">
                                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                                            <div>
                                                                <span class="text-gray-600 font-medium">Method:</span>
                                                                <div class="text-gray-900 font-mono">{{ $httpMethod }}</div>
                                                            </div>
                                                            <div>
                                                                <span class="text-gray-600 font-medium">URL:</span>
                                                                <div class="text-gray-900 font-mono text-xs break-all">{{ $requestInfo['url'] ?? 'Unknown' }}</div>
                                                            </div>
                                                            <div>
                                                                <span class="text-gray-600 font-medium">IP Address:</span>
                                                                <div class="text-gray-900 font-mono">{{ $requestInfo['ip_address'] ?? 'Unknown' }}</div>
                                                            </div>
                                                            <div>
                                                                <span class="text-gray-600 font-medium">User Agent:</span>
                                                                <div class="text-gray-900 font-mono text-xs break-all">{{ $requestInfo['user_agent'] ?? 'Unknown' }}</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <h3 class="text-lg font-medium text-gray-900">Request Data</h3>
                                                    <div class="bg-gray-50 rounded-lg p-4">
                                                        <pre class="text-xs text-gray-700 overflow-x-auto">{{ json_encode($requestInfo, JSON_PRETTY_PRINT) }}</pre>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="tab-content" id="response-{{ $requestId }}">
                                                <div class="space-y-4">
                                                    <h3 class="text-lg font-medium text-gray-900">Response Information</h3>
                                                    <div class="bg-gray-50 rounded-lg p-4">
                                                        <p class="text-gray-600">Response data would be displayed here if available.</p>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="tab-content" id="headers-{{ $requestId }}">
                                                <div class="space-y-4">
                                                    <h3 class="text-lg font-medium text-gray-900">Request Headers</h3>
                                                    <div class="bg-gray-50 rounded-lg p-4">
                                                        <pre class="text-xs text-gray-700 overflow-x-auto">{{ json_encode($requestInfo['headers'] ?? [], JSON_PRETTY_PRINT) }}</pre>
                                                    </div>
                                                    <h3 class="text-lg font-medium text-gray-900">Additional Request Info</h3>
                                                    <div class="bg-gray-50 rounded-lg p-4">
                                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                                            <div>
                                                                <span class="text-gray-600 font-medium">Route Name:</span>
                                                                <div class="text-gray-900 font-mono">{{ $requestInfo['route_name'] ?? 'Unknown' }}</div>
                                                            </div>
                                                            <div>
                                                                <span class="text-gray-600 font-medium">Route Action:</span>
                                                                <div class="text-gray-900 font-mono">{{ $requestInfo['route_action'] ?? 'Unknown' }}</div>
                                                            </div>
                                                            <div>
                                                                <span class="text-gray-600 font-medium">Session ID:</span>
                                                                <div class="text-gray-900 font-mono text-xs break-all">{{ $requestInfo['session_id'] ?? 'Unknown' }}</div>
                                                            </div>
                                                            <div>
                                                                <span class="text-gray-600 font-medium">User ID:</span>
                                                                <div class="text-gray-900 font-mono">{{ $requestInfo['user_id'] ?? 'Unknown' }}</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-8">
                                <div class="text-gray-500 text-lg">No logs found</div>
                                <div class="text-gray-400 text-sm mt-2">Try adjusting your filters or check back later</div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Pagination -->
                <div class="bg-white border-t border-gray-200 px-4 lg:px-6 py-4">
                    <div id="pagination" class="flex justify-center">
                        <!-- Pagination will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentPage = 1;
        let currentFilters = {};
        let expandedRows = new Set();

        // Load logs grouped by request_id
        function loadLogs(page = 1) {
            // Clear expanded rows when loading new logs
            expandedRows.clear();
            
            const perPage = document.getElementById('per-page').value;
            const filters = {
                ...currentFilters,
                page: page,
                per_page: perPage,
                group_by_request: true
            };
            
            fetch('/ac/logs/api?' + new URLSearchParams(filters))
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    displayLogs(data.logs);
                    displayPagination(data.pagination);
                    // Statistics are now handled server-side only
                    currentPage = page;
                })
                .catch(error => {
                    console.error('Error loading logs:', error);
                    const container = document.getElementById('logs-container');
                    container.innerHTML = '<div class="text-center py-8 text-red-600">Error loading logs: ' + error.message + '</div>';
                });
        }

        // Display logs as beautiful cards grouped by request
        function displayLogs(logs) {
            const container = document.getElementById('logs-container');
            
            // Clear existing content completely
            container.innerHTML = '';
            
            // Store logs globally for data access
            window.currentLogs = logs;

            if (!logs || logs.length === 0) {
                container.innerHTML = '<div class="text-center py-8 text-gray-500">No logs found</div>';
                return;
            }

            // Logs are grouped by request_id - each is an array of logs
            logs.forEach((requestLogs, index) => {
                
                // Each requestLogs is an array of logs for that request_id
                const logsArray = Array.isArray(requestLogs) ? requestLogs : [requestLogs];
                if (!logsArray || logsArray.length === 0) return;
                
                const requestId = logsArray[0].request_id;
                const mainLog = logsArray.find(log => log.message.includes('started')) || logsArray[0];
                const completedLog = logsArray.find(log => log.message.includes('completed'));
                const errorLog = logsArray.find(log => log.message.includes('failed'));

                const status = errorLog ? 'error' : (completedLog ? 'success' : 'info');
                const duration = completedLog ? (completedLog.duration || completedLog.properties?.duration_ms || '-') : '-';

                const card = document.createElement('div');
                card.className = 'log-card';
                card.setAttribute('data-request-id', requestId);
                
                const statusClasses = {
                    'success': 'status-success',
                    'error': 'status-error',
                    'info': 'status-info',
                    'warning': 'status-warning'
                };

                // Get additional information
                const controllerName = mainLog.controller || 'Unknown';
                const methodName = mainLog.method || 'Unknown';
                const stepCount = logsArray.length;
                const hasErrors = logsArray.some(log => log.level === 'error');
                const hasWarnings = logsArray.some(log => log.level === 'warning');
                
                // Get enhanced request information
                const requestInfo = mainLog.properties?.request_info || {};
                const httpMethod = requestInfo.method || 'UNKNOWN';
                const requestUrl = requestInfo.url || requestInfo.path || 'Unknown';
                const userAgent = requestInfo.user_agent || 'Unknown';
                const ipAddress = requestInfo.ip_address || 'Unknown';

                card.innerHTML = `
                    <div class="log-header" data-request-id="${requestId}">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between w-full gap-2">
                            <div class="flex flex-col sm:flex-row sm:items-center space-y-2 sm:space-y-0 sm:space-x-4">
                                <div class="flex items-center space-x-2">
                                    <span class="time text-sm font-medium text-gray-600">${new Date(mainLog.created_at).toLocaleTimeString()}</span>
                                    <span class="http-method http-${httpMethod.toLowerCase()}">${httpMethod}</span>
                                    <span class="text-sm text-blue-600 font-medium truncate">${methodName}</span>
                                    <span class="text-gray-400 flex-shrink-0">@</span>
                                    <span class="text-sm font-semibold text-gray-800 truncate">${controllerName}</span>
                                    <span class="status-badge ${statusClasses[status]}">${status.toUpperCase()}</span>
                                    ${duration !== '-' ? `<span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">${duration}ms</span>` : ''}
                                </div>
                                <div class="flex items-center space-x-2 text-gray-500">
                                    <span class="text-xs">${stepCount} step${stepCount !== 1 ? 's' : ''}</span>
                                    ${hasErrors ? '<i class="fas fa-exclamation-circle text-red-500 text-xs"></i>' : ''}
                                    ${hasWarnings ? '<i class="fas fa-exclamation-triangle text-yellow-500 text-xs"></i>' : ''}
                                </div>
                            </div>
                            <div class="flex items-center justify-between sm:justify-end space-x-3">
                                <div class="text-left sm:text-right">
                                    <div class="text-xs text-gray-500 font-mono break-all">${requestId}</div>
                                </div>
                                <i class="fas fa-chevron-down text-gray-400 transition-transform duration-200 flex-shrink-0" id="chevron-${requestId}"></i>
                            </div>
                        </div>
                    </div>
                    <div class="log-content" id="content-${requestId}">
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                            <p>Loading detailed information...</p>
                        </div>
                    </div>
                `;

                container.appendChild(card);
            });
            
            // Add event delegation for card clicks
            container.addEventListener('click', function(e) {
                const logHeader = e.target.closest('.log-header');
                if (logHeader) {
                    const requestId = logHeader.getAttribute('data-request-id');
                    if (requestId) {
                        toggleCard(requestId);
                    }
                }
            });
            
            // Ensure all cards start collapsed
            container.querySelectorAll('.log-content').forEach(content => {
                content.classList.remove('show');
                console.log('Collapsing content:', content.id, 'has show class:', content.classList.contains('show'));
            });
        }

        // Toggle card expansion with accordion behavior
        function toggleCard(requestId) {
            const content = document.getElementById(`content-${requestId}`);
            const chevron = document.getElementById(`chevron-${requestId}`);
            
            if (!content || !chevron) {
                console.error('Could not find content or chevron for:', requestId);
                return;
            }
            
            if (expandedRows.has(requestId)) {
                content.classList.remove('show');
                chevron.classList.remove('fa-chevron-up');
                chevron.classList.add('fa-chevron-down');
            }
            
            if (expandedRows.has(requestId)) {
                content.classList.remove('show');
                chevron.classList.remove('fa-chevron-up');
                chevron.classList.add('fa-chevron-down');
                chevron.style.transform = 'rotate(0deg)';
                expandedRows.delete(requestId);
            } else {
                
                // Close all other cards (accordion behavior)
                expandedRows.forEach(openRequestId => {
                    const openContent = document.getElementById(`content-${openRequestId}`);
                    const openChevron = document.getElementById(`chevron-${openRequestId}`);
                    if (openContent && openChevron) {
                        openContent.classList.remove('show');
                        openChevron.classList.remove('fa-chevron-up');
                        openChevron.classList.add('fa-chevron-down');
                        openChevron.style.transform = 'rotate(0deg)';
                    }
                });
                expandedRows.clear();
                
                // Open this card
                content.classList.add('show');
                chevron.classList.remove('fa-chevron-down');
                chevron.classList.add('fa-chevron-up');
                chevron.style.transform = 'rotate(180deg)';
                expandedRows.add(requestId);
                
                // Load detailed data if not already loaded
                if (!content.dataset.loaded) {
                    loadLogDetails(requestId, content);
                }
            }
        }

        // Load detailed log data from API
        async function loadLogDetails(requestId, contentElement) {
            try {
                const response = await fetch(`/ac/logs/api/details?request_id=${requestId}`);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                // Render the detailed content
                renderDetailedContent(contentElement, data);
                
                // Mark as loaded
                contentElement.dataset.loaded = 'true';
                
            } catch (error) {
                console.error('Error loading log details:', error);
                contentElement.innerHTML = `
                    <div class="text-center py-8 text-red-500">
                        <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                        <p>Error loading details: ${error.message}</p>
                    </div>
                `;
            }
        }

        // Render detailed content from API data
        function renderDetailedContent(contentElement, data) {
            const { steps, request_info, response_data, main_log, duration } = data;
            
            contentElement.innerHTML = `
                <div class="space-y-3">
                    <!-- Summary Info -->
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="text-gray-600 font-medium">Controller:</span>
                                <div class="text-gray-900 font-mono">${main_log.controller || 'Unknown'}</div>
                            </div>
                            <div>
                                <span class="text-gray-600 font-medium">Method:</span>
                                <div class="text-gray-900 font-mono">${main_log.method || 'Unknown'}</div>
                            </div>
                            <div>
                                <span class="text-gray-600 font-medium">Duration:</span>
                                <div class="text-gray-900 font-mono">${duration}</div>
                            </div>
                            <div>
                                <span class="text-gray-600 font-medium">Steps:</span>
                                <div class="text-gray-900 font-mono">${steps.length}</div>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-t border-gray-200 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-600 font-medium">HTTP Method:</span>
                                <span class="http-method http-${(request_info.method || 'get').toLowerCase()} ml-2">${request_info.method || 'GET'}</span>
                            </div>
                            <div>
                                <span class="text-gray-600 font-medium">IP Address:</span>
                                <span class="text-gray-900 font-mono ml-2">${request_info.ip_address || 'Unknown'}</span>
                            </div>
                            <div class="md:col-span-2">
                                <span class="text-gray-600 font-medium">URL:</span>
                                <span class="text-gray-900 font-mono ml-2 break-all">${request_info.url || 'Unknown'}</span>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-t border-gray-200">
                            <span class="text-gray-600 font-medium">Trace ID:</span>
                            <span class="text-gray-900 font-mono text-sm">${data.request_id}</span>
                        </div>
                    </div>
                    
                    <!-- Tabs -->
                    <div class="border-b border-gray-200 pb-2">
                        <nav class="flex space-x-1">
                            <button class="tab-button active" data-tab="steps-${data.request_id}">
                                <i class="fas fa-list mr-1"></i>Steps
                            </button>
                            <button class="tab-button" data-tab="request-${data.request_id}">
                                <i class="fas fa-arrow-right mr-1"></i>Request
                            </button>
                            <button class="tab-button" data-tab="response-${data.request_id}">
                                <i class="fas fa-arrow-left mr-1"></i>Response
                            </button>
                            <button class="tab-button" data-tab="headers-${data.request_id}">
                                <i class="fas fa-headers mr-1"></i>Headers
                            </button>
                        </nav>
                    </div>

                    <!-- Tab Content -->
                    <div class="tab-content active" id="steps-${data.request_id}">
                        <div class="space-y-2">
                            ${steps.map(step => `
                                <div class="step-item" style="${step.indent_style}">
                                    <div class="flex items-start space-x-3">
                                        <div class="step-icon ${step.step_icon_class} flex-shrink-0">
                                            ${step.visual_indicator}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                                <div class="flex items-center flex-wrap gap-2">
                                                    <p class="font-medium text-gray-900 text-sm break-words">${step.log.message}</p>
                                                    <span class="category-badge category-${step.category.toLowerCase().replace(/\s+/g, '-')}">${step.category}</span>
                                                    ${step.log.call_depth > 0 ? `<span class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded">Level ${step.log.call_depth}</span>` : ''}
                                                </div>
                                                <div class="flex items-center space-x-2 text-xs text-gray-500 flex-wrap">
                                                    ${step.step_duration ? `<span class="font-mono">${step.step_duration}ms</span>` : ''}
                                                    ${step.memory_used ? `<span class="font-mono">${Math.round(step.memory_used / 1024)}KB</span>` : ''}
                                                    <span class="text-gray-400">${new Date(step.log.created_at).toLocaleTimeString()}</span>
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${getLevelClass(step.log.level)}">
                                                        ${step.log.level}
                                                    </span>
                                                </div>
                                            </div>
                                            ${step.data_display ? `
                                                <div class="mt-1 text-xs text-gray-600">
                                                    ${step.data_display}
                                                </div>
                                            ` : ''}
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>

                    <div class="tab-content" id="request-${data.request_id}">
                        <div class="space-y-4">
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <h4 class="font-semibold text-blue-900 mb-2">Request Information</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <span class="text-blue-700 font-medium">Method:</span>
                                        <span class="http-method http-${(request_info.method || 'get').toLowerCase()} ml-2">${request_info.method || 'GET'}</span>
                                    </div>
                                    <div>
                                        <span class="text-blue-700 font-medium">URL:</span>
                                        <span class="text-blue-900 font-mono ml-2 break-all">${request_info.url || 'Unknown'}</span>
                                    </div>
                                    <div>
                                        <span class="text-blue-700 font-medium">IP Address:</span>
                                        <span class="text-blue-900 font-mono ml-2">${request_info.ip_address || 'Unknown'}</span>
                                    </div>
                                    <div>
                                        <span class="text-blue-700 font-medium">User Agent:</span>
                                        <span class="text-blue-900 font-mono ml-2 break-all">${request_info.user_agent || 'Unknown'}</span>
                                    </div>
                                </div>
                            </div>
                            
                            ${request_info.headers ? `
                                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                    <h4 class="font-semibold text-gray-900 mb-2">Headers</h4>
                                    <div class="json-viewer">
                                        <pre>${JSON.stringify(request_info.headers, null, 2)}</pre>
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                    </div>

                    <div class="tab-content" id="response-${data.request_id}">
                        ${response_data && Object.keys(response_data).length > 0 ? `
                            <div class="space-y-4">
                                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                    <h4 class="font-semibold text-green-900 mb-2">Response Information</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                        <div>
                                            <span class="text-green-700 font-medium">Status Code:</span>
                                            <span class="text-green-900 font-mono ml-2">${response_data.status_code || 'N/A'}</span>
                                        </div>
                                        <div>
                                            <span class="text-green-700 font-medium">Memory Used:</span>
                                            <span class="text-green-900 font-mono ml-2">${response_data.memory_used || 'N/A'}</span>
                                        </div>
                                        <div>
                                            <span class="text-green-700 font-medium">Content Type:</span>
                                            <span class="text-green-900 font-mono ml-2">${response_data.content_type || 'N/A'}</span>
                                        </div>
                                        <div>
                                            <span class="text-green-700 font-medium">Duration:</span>
                                            <span class="text-green-900 font-mono ml-2">${duration}</span>
                                        </div>
                                    </div>
                                </div>
                                
                                ${response_data.data && Object.keys(response_data.data).length > 0 ? `
                                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                        <h4 class="font-semibold text-gray-900 mb-2">Response Data</h4>
                                        <div class="json-viewer">
                                            <pre>${JSON.stringify(response_data.data, null, 2)}</pre>
                                        </div>
                                    </div>
                                ` : ''}
                            </div>
                        ` : `
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                                <p>No response data available</p>
                            </div>
                        `}
                    </div>

                    <div class="tab-content" id="headers-${data.request_id}">
                        <div class="space-y-4">
                            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                                <h4 class="font-semibold text-purple-900 mb-2">Request Headers</h4>
                                <div class="json-viewer">
                                    <pre>${JSON.stringify(request_info.headers || {}, null, 2)}</pre>
                                </div>
                            </div>
                            
                            ${response_data?.response_headers ? `
                                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                                    <h4 class="font-semibold text-purple-900 mb-2">Response Headers</h4>
                                    <div class="json-viewer">
                                        <pre>${JSON.stringify(response_data.response_headers, null, 2)}</pre>
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
        }

        // Tab switching
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('tab-button') || e.target.closest('.tab-button')) {
                e.preventDefault();
                e.stopPropagation();
                
                const tabButton = e.target.classList.contains('tab-button') ? e.target : e.target.closest('.tab-button');
                const tabId = tabButton.getAttribute('data-tab');
                const requestId = tabId.split('-')[1];
                
                
                // Find the parent card container
                const cardContainer = tabButton.closest('.log-card');
                if (!cardContainer) {
                    console.error('Could not find card container');
                    return;
                }
                
                // Remove active class from all tabs in this card only
                const allTabs = cardContainer.querySelectorAll('.tab-button');
                allTabs.forEach(tab => tab.classList.remove('active'));
                
                // Hide all tab content in this card only
                const allContent = cardContainer.querySelectorAll('.tab-content');
                allContent.forEach(content => content.classList.remove('active'));
                
                // Activate clicked tab
                tabButton.classList.add('active');
                const targetContent = cardContainer.querySelector(`#${tabId}`);
                if (targetContent) {
                    targetContent.classList.add('active');
                } else {
                    console.error('Could not find content for tab:', tabId, 'Available content:', cardContainer.querySelectorAll('.tab-content'));
                }
            }
        });

        // Display pagination
        function displayPagination(pagination) {
            const paginationDiv = document.getElementById('pagination');
            paginationDiv.innerHTML = `
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing ${((pagination.current_page - 1) * pagination.per_page) + 1} to ${Math.min(pagination.current_page * pagination.per_page, pagination.total)} of ${pagination.total} entries
                    </div>
                    <div class="flex space-x-2">
                        ${pagination.current_page > 1 ? `<button onclick="loadLogs(${pagination.current_page - 1})" class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50">Previous</button>` : ''}
                        ${pagination.has_more ? `<button onclick="loadLogs(${pagination.current_page + 1})" class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50">Next</button>` : ''}
                    </div>
                </div>
            `;
        }

        // Get CSS class for log level
        function getLevelClass(level) {
            switch(level) {
                case 'emergency':
                case 'alert':
                case 'critical':
                    return 'bg-red-900 text-white';
                case 'error':
                    return 'bg-red-100 text-red-800';
                case 'warning':
                    return 'bg-yellow-100 text-yellow-800';
                case 'notice':
                    return 'bg-green-100 text-green-800';
                case 'info':
                    return 'bg-blue-100 text-blue-800';
                case 'debug':
                    return 'bg-gray-100 text-gray-800';
                default:
                    return 'bg-gray-100 text-gray-800';
            }
        }

        // Update statistics
        function updateStatistics(stats) {
            const original = stats.original || stats;
            document.getElementById('total-logs').textContent = original.total_logs || 0;
            document.getElementById('success-logs').textContent = original.by_level?.info || 0;
            document.getElementById('warning-logs').textContent = original.by_level?.warning || 0;
            // Include emergency, alert, critical, and error in error count
            const errorCount = (original.by_level?.error || 0) + 
                              (original.by_level?.emergency || 0) + 
                              (original.by_level?.alert || 0) + 
                              (original.by_level?.critical || 0);
            document.getElementById('error-logs').textContent = errorCount;
        }

        // Display pagination
        function displayPagination(pagination) {
            const container = document.getElementById('pagination');
            if (!pagination || pagination.last_page <= 1) {
                container.innerHTML = '';
                return;
            }

            const { current_page, last_page, total, per_page } = pagination;
            
            // Ensure all values are numbers
            const currentPage = parseInt(current_page) || 1;
            const lastPage = parseInt(last_page) || 1;
            const totalItems = parseInt(total) || 0;
            const perPage = parseInt(per_page) || 10;
            
            // Validate values to prevent negative numbers
            const startItem = Math.max(1, (currentPage - 1) * perPage + 1);
            const endItem = Math.min(currentPage * perPage, totalItems);
            
            // If no items, show 0
            const displayStart = totalItems > 0 ? startItem : 0;
            const displayEnd = totalItems > 0 ? endItem : 0;

            container.innerHTML = `
                <div class="flex flex-col sm:flex-row sm:items-center justify-between w-full gap-4">
                    <div class="text-sm text-gray-700 text-center sm:text-left">
                        Showing <span class="font-semibold">${displayStart}</span> to <span class="font-semibold">${displayEnd}</span> of <span class="font-semibold">${totalItems}</span> results
                    </div>
                    <div class="flex items-center justify-center space-x-1 sm:space-x-2">
                        <button onclick="loadLogs(1)" class="pagination-btn px-2 lg:px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50" ${currentPage === 1 ? 'disabled' : ''}>
                            <i class="fas fa-angle-double-left"></i>
                        </button>
                        <button onclick="loadLogs(${currentPage - 1})" class="pagination-btn px-2 lg:px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50" ${currentPage === 1 ? 'disabled' : ''}>
                            <i class="fas fa-angle-left"></i>
                        </button>
                        
                        <div class="flex space-x-1">
                            ${Array.from({ length: Math.min(5, lastPage) }, (_, i) => {
                                let pageNum;
                                if (lastPage <= 5) {
                                    pageNum = i + 1;
                                } else if (currentPage <= 3) {
                                    pageNum = i + 1;
                                } else if (currentPage >= lastPage - 2) {
                                    pageNum = lastPage - 4 + i;
                                } else {
                                    pageNum = currentPage - 2 + i;
                                }
                                
                                return `
                                    <button onclick="loadLogs(${pageNum})" class="pagination-btn px-2 lg:px-3 py-2 text-sm font-medium rounded-lg ${
                                        pageNum === currentPage 
                                            ? 'bg-blue-600 text-white border-blue-600' 
                                            : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-50'
                                    }">
                                        ${pageNum}
                                    </button>
                                `;
                            }).join('')}
                        </div>
                        
                        <button onclick="loadLogs(${currentPage + 1})" class="pagination-btn px-2 lg:px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50" ${currentPage === lastPage ? 'disabled' : ''}>
                            <i class="fas fa-angle-right"></i>
                        </button>
                        <button onclick="loadLogs(${lastPage})" class="pagination-btn px-2 lg:px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50" ${currentPage === lastPage ? 'disabled' : ''}>
                            <i class="fas fa-angle-double-right"></i>
                        </button>
                    </div>
                </div>
            `;
        }

        // Apply filters function
        function applyFilters() {
            currentFilters = {};
            
            // Only include non-empty filter values
            const level = document.getElementById('level-filter').value;
            if (level && level !== '') {
                currentFilters.level = level;
            }
            
            const dateFrom = document.getElementById('date-from').value;
            if (dateFrom && dateFrom !== '') {
                currentFilters.date_from = dateFrom;
            }
            
            const dateTo = document.getElementById('date-to').value;
            if (dateTo && dateTo !== '') {
                currentFilters.date_to = dateTo;
            }
            
            const search = document.getElementById('search').value;
            if (search && search.trim() !== '') {
                currentFilters.search = search.trim();
            }
            
            loadLogs(1);
        }




        // Event listeners

        // Enter key support for form inputs
        document.getElementById('search').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });

        document.getElementById('level-filter').addEventListener('change', applyFilters);
        document.getElementById('date-from').addEventListener('change', applyFilters);
        document.getElementById('date-to').addEventListener('change', applyFilters);
        document.getElementById('per-page').addEventListener('change', () => {
            loadLogs(1);
        });


        // Clean up interval when page is unloaded
        window.addEventListener('beforeunload', () => {
            stopLiveMode();
        });

        // Show data value in popup
        function showDataValue(key, value) {
            try {
                const parsedValue = JSON.parse(value);
                const formattedValue = typeof parsedValue === 'object' ? JSON.stringify(parsedValue, null, 2) : parsedValue;
                alert(`${key}: ${formattedValue}`);
            } catch (e) {
                alert(`${key}: ${value}`);
            }
        }

        // Data viewing functions
        function showAllData(requestId, logId) {
            // Find the log entry in the current logs array
            let log = null;
            for (let i = 0; i < window.currentLogs.length; i++) {
                const requestLogs = window.currentLogs[i];
                if (Array.isArray(requestLogs)) {
                    log = requestLogs.find(l => l.id == logId);
                    if (log) break;
                } else if (requestLogs && requestLogs.id == logId) {
                    log = requestLogs;
                    break;
                }
            }
            
            if (log && log.properties) {
                const title = `${log.controller} → ${log.method}`;
                // Filter out duration and memory from modal data
                const filteredProperties = Object.fromEntries(
                    Object.entries(log.properties).filter(([key]) => !['duration_ms', 'memory_used'].includes(key))
                );
                showDataModal(title, filteredProperties, log.message);
            }
        }

        function showDataModal(title, data, message) {
            // Create modal if it doesn't exist
            let modal = document.getElementById('data-modal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'data-modal';
                modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden';
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
            document.getElementById('modal-title').textContent = title;
            document.getElementById('modal-message').textContent = message;
            document.getElementById('modal-content').textContent = JSON.stringify(data, null, 2);

            // Show modal
            modal.classList.remove('hidden');
        }

        function closeDataModal() {
            const modal = document.getElementById('data-modal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        // Close modal when clicking outside
        document.addEventListener('click', (e) => {
            const modal = document.getElementById('data-modal');
            if (e.target === modal) {
                closeDataModal();
            }
        });



        // Store current logs globally for data access
        window.currentLogs = null;

        // Mobile drawer functionality
        function initMobileDrawer() {
            const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
            const mobileFilterToggle = document.getElementById('mobile-filter-toggle');
            const mobileMenuClose = document.getElementById('mobile-menu-close');
            const mobileOverlay = document.getElementById('mobile-overlay');
            const filtersSidebar = document.getElementById('filters-sidebar');

            function openDrawer() {
                filtersSidebar.classList.remove('-translate-x-full');
                filtersSidebar.classList.add('show');
                mobileOverlay.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }

            function closeDrawer() {
                filtersSidebar.classList.add('-translate-x-full');
                filtersSidebar.classList.remove('show');
                mobileOverlay.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }

            // Toggle drawer
            if (mobileMenuToggle) {
                mobileMenuToggle.addEventListener('click', openDrawer);
            }
            if (mobileFilterToggle) {
                mobileFilterToggle.addEventListener('click', openDrawer);
            }
            if (mobileMenuClose) {
                mobileMenuClose.addEventListener('click', closeDrawer);
            }
            if (mobileOverlay) {
                mobileOverlay.addEventListener('click', closeDrawer);
            }

            // Close drawer on escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && filtersSidebar.classList.contains('show')) {
                    closeDrawer();
                }
            });

            // Close drawer when applying filters on mobile
            const applyButton = document.getElementById('apply-filters');
            if (applyButton) {
                applyButton.addEventListener('click', function() {
                    if (window.innerWidth < 1024) { // lg breakpoint
                        closeDrawer();
                    }
                });
            }
        }

        // Load initial data
        loadLogs();
        
        // Initialize mobile drawer
        initMobileDrawer();
    </script>
</body>
</html>