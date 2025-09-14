@if(!empty($steps))
    <div class="space-y-4">
        @foreach($steps as $step)
            <div class="step-item" style="{{ $step['indent_style'] ?? '' }}">
                <div class="flex items-start space-x-3">
                    <div class="step-icon {{ $step['step_icon_class'] ?? 'info' }} flex-shrink-0">
                        {{ $step['visual_indicator'] ?? 'L1' }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div class="flex items-center flex-wrap gap-2">
                                <p class="font-medium text-gray-900 text-sm break-words">{{ $step['log']['message'] ?? 'Unknown' }}</p>
                                <span class="category-badge category-{{ strtolower(str_replace(' ', '-', $step['category'] ?? 'Actions')) }}">{{ $step['category'] ?? 'Actions' }}</span>
                                @if(($step['log']['call_depth'] ?? 0) > 0)
                                    <span class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded">Level {{ $step['log']['call_depth'] }}</span>
                                @endif
                            </div>
                            <div class="flex items-center space-x-2 text-xs text-gray-500 flex-wrap">
                                @if($step['step_duration'] ?? '')
                                    <span class="font-mono">{{ $step['step_duration'] }}ms</span>
                                @endif
                                <span class="text-gray-400">{{ \Carbon\Carbon::parse($step['log']['created_at'] ?? now())->format('H:i:s') }}</span>
                                @php
                                    $level = $step['log']['level'] ?? 'info';
                                    $levelClass = 'bg-gray-100 text-gray-800'; // Default
                                    
                                    switch (strtolower($level)) {
                                        case 'error':
                                            $levelClass = 'bg-red-100 text-red-800';
                                            break;
                                        case 'warning':
                                            $levelClass = 'bg-yellow-100 text-yellow-800';
                                            break;
                                        case 'info':
                                            $levelClass = 'bg-blue-100 text-blue-800';
                                            break;
                                        case 'debug':
                                            $levelClass = 'bg-gray-100 text-gray-800';
                                            break;
                                        case 'success':
                                            $levelClass = 'bg-green-100 text-green-800';
                                            break;
                                        case 'critical':
                                            $levelClass = 'bg-red-200 text-red-900';
                                            break;
                                        case 'alert':
                                            $levelClass = 'bg-orange-100 text-orange-800';
                                            break;
                                        case 'emergency':
                                            $levelClass = 'bg-red-300 text-red-900';
                                            break;
                                    }
                                @endphp
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $levelClass }}">
                                    {{ $level }}
                                </span>
                            </div>
                        </div>
                        @if($step['data_display'] ?? '')
                            <div class="mt-1 text-xs text-gray-600">{!! $step['data_display'] !!}</div>
                        @endif
                        @if(isset($step['log']['properties']['response_data']) && $step['log']['properties']['response_data'] !== null)
                            <div class="mt-2">
                                <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                                    <div class="flex items-center mb-2">
                                        <i class="fas fa-arrow-left text-green-600 text-sm mr-2"></i>
                                        <span class="text-xs font-medium text-green-800">Response Data</span>
                                    </div>
                                    <div class="bg-gray-900 rounded p-2 overflow-auto max-h-32">
                                        <pre class="text-green-400 text-xs font-mono">{{ json_encode($step['log']['properties']['response_data'], JSON_PRETTY_PRINT) }}</pre>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="text-center py-8 text-gray-500">
        <i class="fas fa-info-circle text-2xl mb-2"></i>
        <p>No steps available for this request</p>
    </div>
@endif
