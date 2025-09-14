{{-- Steps Partial --}}
<div class="space-y-2">
    @if(empty($steps))
        <div class="text-center py-4 text-gray-500">
            <i class="fas fa-info-circle text-2xl mb-2"></i>
            <p>No steps data available</p>
            <p class="text-xs mt-1">Debug: steps array is empty</p>
        </div>
    @else
        <div class="text-xs text-gray-400 mb-2">
            Debug: Found {{ count($steps) }} steps
        </div>
    @endif
    
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
