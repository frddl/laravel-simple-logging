{{-- Log Details Partial --}}
<div class="space-y-3">
    {{-- Summary Info --}}
    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
                <span class="text-gray-600 font-medium">Controller:</span>
                <div class="text-gray-900 font-mono">{{ $mainLog['controller'] ?? 'Unknown' }}</div>
            </div>
            <div>
                <span class="text-gray-600 font-medium">Method:</span>
                <div class="text-gray-900 font-mono">{{ $mainLog['method'] ?? 'Unknown' }}</div>
            </div>
            <div>
                <span class="text-gray-600 font-medium">Duration:</span>
                <div class="text-gray-900 font-mono">{{ $duration }}</div>
            </div>
            <div>
                <span class="text-gray-600 font-medium">Steps:</span>
                <div class="text-gray-900 font-mono">{{ count($steps) }}</div>
            </div>
        </div>
        <div class="mt-3 pt-3 border-t border-gray-200 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-gray-600 font-medium">HTTP Method:</span>
                <span class="http-method http-{{ strtolower($requestInfo['http_method'] ?? 'get') }} ml-2">{{ $requestInfo['http_method'] ?? 'GET' }}</span>
            </div>
            <div>
                <span class="text-gray-600 font-medium">IP Address:</span>
                <span class="text-gray-900 font-mono ml-2">{{ $requestInfo['ip_address'] ?? 'Unknown' }}</span>
            </div>
            <div class="md:col-span-2">
                <span class="text-gray-600 font-medium">URL:</span>
                <span class="text-gray-900 font-mono ml-2 break-all">{{ $requestInfo['url'] ?? 'Unknown' }}</span>
            </div>
        </div>
        <div class="mt-3 pt-3 border-t border-gray-200">
            <span class="text-gray-600 font-medium">Trace ID:</span>
            <span class="text-gray-900 font-mono text-sm">{{ $requestId }}</span>
        </div>
    </div>
    
    {{-- Tabs --}}
    <div class="border-b border-gray-200 pb-2">
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

    {{-- Tab Content --}}
    <div class="tab-content active" id="steps-{{ $requestId }}">
        <div class="space-y-2">
            @foreach($steps as $step)
                <div class="step-item" style="{{ $step['indent_style'] }}">
                    <div class="flex items-start space-x-3">
                        <div class="step-icon {{ $step['step_icon_class'] }} flex-shrink-0">
                            {!! $step['visual_indicator'] !!}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div class="flex items-center flex-wrap gap-2">
                                    <p class="font-medium text-gray-900 text-sm break-words">{{ $step['log']['message'] }}</p>
                                    <span class="category-badge category-{{ strtolower(str_replace(' ', '-', $step['category'])) }}">{{ $step['category'] }}</span>
                                    @if($step['log']['call_depth'] > 0)
                                        <span class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded">Level {{ $step['log']['call_depth'] }}</span>
                                    @endif
                                </div>
                                <div class="flex items-center space-x-2 text-xs text-gray-500 flex-wrap">
                                    @if($step['step_duration'])
                                        <span class="font-mono">{{ $step['step_duration'] }}ms</span>
                                    @endif
                                    @if($step['memory_used'])
                                        <span class="font-mono">{{ round($step['memory_used'] / 1024) }}KB</span>
                                    @endif
                                    <span class="text-gray-400">{{ \Carbon\Carbon::parse($step['log']['created_at'])->format('H:i:s') }}</span>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $step['step_icon_class'] }}">
                                        {{ $step['log']['level'] }}
                                    </span>
                                </div>
                            </div>
                            @if($step['data_display'])
                                <div class="mt-1 text-xs text-gray-600">
                                    {!! $step['data_display'] !!}
                                </div>
                            @endif
                            @if($step['log']['properties'] && count($step['log']['properties']) > 0)
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach($step['log']['properties'] as $key => $value)
                                        @if(!in_array($key, ['duration_ms', 'memory_used', 'call_depth', 'log_type', 'category', 'visual_indicator']))
                                            @php
                                                $displayValue = is_array($value) ? '[Array]' : (is_string($value) && strlen($value) > 15 ? substr($value, 0, 15) . '...' : $value);
                                                $chipClass = str_contains($key, 'error') || str_contains($key, 'exception') ? 'bg-red-100 hover:bg-red-200' : 
                                                           (str_contains($key, 'success') || str_contains($key, 'completed') ? 'bg-green-100 hover:bg-green-200' : 
                                                           'bg-blue-100 hover:bg-blue-200');
                                                $level = $step['log']['call_depth'] ?? 1;
                                                $method = $step['log']['message'] ?? 'Unknown';
                                            @endphp
                                            <span class="inline-block {{ $chipClass }} cursor-pointer px-2 py-1 rounded mr-1 transition-colors text-xs" 
                                                  onclick="showDataValue('{{ $key }}', '{{ addslashes(json_encode($value)) }}', 'L{{ $level }}', '{{ addslashes($method) }}')" 
                                                  title="Click to view full value">
                                                {{ $key }}: {{ htmlspecialchars($displayValue) }}
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Request Tab --}}
    <div class="tab-content" id="request-{{ $requestId }}">
        <div class="space-y-4">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 class="font-semibold text-blue-900 mb-2">Request Information</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                    <div>
                        <span class="text-blue-700 font-medium">HTTP Method:</span>
                        <span class="http-method http-{{ strtolower($requestInfo['http_method'] ?? 'get') }} ml-2">{{ $requestInfo['http_method'] ?? 'GET' }}</span>
                    </div>
                    <div>
                        <span class="text-blue-700 font-medium">URL:</span>
                        <span class="text-blue-900 font-mono ml-2 break-all">{{ $requestInfo['url'] ?? 'Unknown' }}</span>
                    </div>
                    <div>
                        <span class="text-blue-700 font-medium">IP Address:</span>
                        <span class="text-blue-900 font-mono ml-2">{{ $requestInfo['ip_address'] ?? 'Unknown' }}</span>
                    </div>
                    <div>
                        <span class="text-blue-700 font-medium">User Agent:</span>
                        <span class="text-blue-900 font-mono ml-2 break-all">{{ $requestInfo['user_agent'] ?? 'Unknown' }}</span>
                    </div>
                </div>
            </div>
            
            @if($requestInfo['headers'] ?? false)
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-2">Headers</h4>
                    <div class="json-viewer">
                        <pre>{{ json_encode($requestInfo['headers'], JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Response Tab --}}
    <div class="tab-content" id="response-{{ $requestId }}">
        @if($responseData && count($responseData) > 0)
            <div class="space-y-4">
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <h4 class="font-semibold text-green-900 mb-2">Response Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                        <div>
                            <span class="text-green-700 font-medium">Status Code:</span>
                            <span class="text-green-900 font-mono ml-2">{{ $responseData['status_code'] ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-green-700 font-medium">Memory Used:</span>
                            <span class="text-green-900 font-mono ml-2">{{ $responseData['memory_used'] ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-green-700 font-medium">Content Type:</span>
                            <span class="text-green-900 font-mono ml-2">{{ $responseData['content_type'] ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-green-700 font-medium">Duration:</span>
                            <span class="text-green-900 font-mono ml-2">{{ $duration }}</span>
                        </div>
                    </div>
                </div>
                
                @if($responseData['data'] ?? false)
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-900 mb-2">Response Data</h4>
                        <div class="json-viewer">
                            <pre>{{ json_encode($responseData['data'], JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    </div>
                @endif
            </div>
        @else
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                <p>No response data available</p>
            </div>
        @endif
    </div>

    {{-- Headers Tab --}}
    <div class="tab-content" id="headers-{{ $requestId }}">
        <div class="space-y-4">
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                <h4 class="font-semibold text-purple-900 mb-2">Request Headers</h4>
                <div class="json-viewer">
                    <pre>{{ json_encode($requestInfo['headers'] ?? [], JSON_PRETTY_PRINT) }}</pre>
                </div>
            </div>
            
            @if($responseData['response_headers'] ?? false)
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                    <h4 class="font-semibold text-purple-900 mb-2">Response Headers</h4>
                    <div class="json-viewer">
                        <pre>{{ json_encode($responseData['response_headers'], JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
