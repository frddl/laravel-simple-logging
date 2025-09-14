@if(!empty($requestInfo))
    <div class="space-y-6">
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
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ $requestInfo['http_method'] ?? 'Unknown' }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">IP Address</span>
                                    <span class="font-mono text-sm text-gray-900">{{ $requestInfo['ip_address'] ?? 'Unknown' }}</span>
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
                                    <span class="font-mono text-sm text-gray-900">{{ $requestInfo['controller'] ?? 'Unknown' }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Method</span>
                                    <span class="font-mono text-sm text-gray-900">{{ $requestInfo['method'] ?? 'Unknown' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="md:col-span-1">
                        <div class="bg-gray-50 rounded-lg p-3">
                            <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                                <i class="fas fa-clock mr-2 text-orange-500"></i>
                                Timing Information
                            </h4>
                            <div class="space-y-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Request Time</span>
                                    <span class="font-mono text-sm text-gray-900">{{ $requestInfo['request_time'] ?? 'Unknown' }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Duration</span>
                                    <span class="font-mono text-sm text-gray-900">{{ $requestInfo['duration'] ?? 'Unknown' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Headers Section -->
                @if(!empty($requestInfo['headers']))
                    <div class="mt-6">
                        <div class="bg-gray-50 rounded-lg p-3">
                            <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                                <i class="fas fa-list mr-2 text-indigo-500"></i>
                                Request Headers
                            </h4>
                            <div class="bg-gray-900 rounded-lg p-4 overflow-auto max-h-64">
                                <pre class="text-green-400 text-sm font-mono">{{ json_encode($requestInfo['headers'], JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </div>
                    </div>
                @endif
                
                <!-- URL and User Agent -->
                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-gray-50 rounded-lg p-3">
                        <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                            <i class="fas fa-link mr-2 text-blue-500"></i>
                            Request URL
                        </h4>
                        <div class="font-mono text-sm text-gray-900 break-all">{{ $requestInfo['url'] ?? 'Unknown' }}</div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                            <i class="fas fa-user mr-2 text-purple-500"></i>
                            User Agent
                        </h4>
                        <div class="font-mono text-sm text-gray-900 break-all">{{ $requestInfo['user_agent'] ?? 'Unknown' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@else
    <div class="text-center py-8 text-gray-500">
        <i class="fas fa-info-circle text-2xl mb-2"></i>
        <p>No request information available</p>
    </div>
@endif
