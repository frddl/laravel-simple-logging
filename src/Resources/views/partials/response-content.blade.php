@if(!empty($allResponses) || !empty($responseInfo))
    <div class="space-y-6">
        @if(!empty($allResponses))
            <!-- All Responses List -->
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-4 py-3 border-b border-gray-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-list text-blue-600 text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-semibold text-gray-900">All Method Responses</h3>
                            <p class="text-sm text-gray-600">All function responses in execution order</p>
                        </div>
                    </div>
                </div>
                <div class="p-4">
                    <div class="space-y-4">
                        @foreach($allResponses as $index => $response)
                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-3">
                                            {{ $index + 1 }}
                                        </span>
                                        <h4 class="text-sm font-medium text-gray-900">{{ $response['method_name'] }}</h4>
                                    </div>
                                    <div class="flex items-center space-x-2 text-xs text-gray-500">
                                        @if($response['duration_ms'])
                                            <span class="font-mono">{{ $response['duration_ms'] }}ms</span>
                                        @endif
                                        @if($response['memory_used'])
                                            <span class="font-mono">{{ $response['memory_used'] }}</span>
                                        @endif
                                        <span class="text-gray-400">{{ \Carbon\Carbon::parse($response['timestamp'])->format('H:i:s') }}</span>
                                    </div>
                                </div>
                                <div class="bg-gray-900 rounded-lg p-3 overflow-auto max-h-48">
                                    <pre class="text-green-400 text-sm font-mono">{{ json_encode($response['response_data'], JSON_PRETTY_PRINT) }}</pre>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @if(!empty($responseInfo))
            <!-- Main Response Information Card -->
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 px-4 py-3 border-b border-gray-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-arrow-left text-green-600 text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-semibold text-gray-900">Main Response Information</h3>
                            <p class="text-sm text-gray-600">Primary response details and data</p>
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
                                        @php
                                            $statusCode = $responseInfo['status_code'] ?? 'N/A';
                                            $statusClass = 'bg-gray-100 text-gray-800'; // Default
                                            
                                            if (is_numeric($statusCode)) {
                                                $code = (int) $statusCode;
                                                if ($code >= 200 && $code < 300) {
                                                    $statusClass = 'bg-green-100 text-green-800'; // 2xx Success
                                                } elseif ($code >= 300 && $code < 400) {
                                                    $statusClass = 'bg-blue-100 text-blue-800'; // 3xx Redirection
                                                } elseif ($code >= 400 && $code < 500) {
                                                    $statusClass = 'bg-yellow-100 text-yellow-800'; // 4xx Client Error
                                                } elseif ($code >= 500) {
                                                    $statusClass = 'bg-red-100 text-red-800'; // 5xx Server Error
                                                }
                                            }
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">{{ $statusCode }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Content Type</span>
                                        <span class="font-mono text-sm text-gray-900">{{ $responseInfo['content_type'] ?? 'N/A' }}</span>
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
                                        <span class="font-mono text-sm text-gray-900">{{ $responseInfo['memory_used'] ?? 'N/A' }}</span>
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
                                    <pre class="text-green-400 text-sm font-mono">{{ json_encode($responseInfo['response_data'] ?? [], JSON_PRETTY_PRINT) }}</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@else
    <div class="text-center py-8 text-gray-500">
        <i class="fas fa-info-circle text-2xl mb-2"></i>
        <p>No response information available</p>
    </div>
@endif
