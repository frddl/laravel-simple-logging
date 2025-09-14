<?php

namespace Frddl\LaravelSimpleLogging\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LogEntry extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'request_id',
        'level',
        'message',
        'context',
        'properties',
        'controller',
        'method',
        'call_depth',
        'ip_address',
        'user_agent',
        'url',
        'http_method',
        'created_at',
    ];

    protected $casts = [
        'context' => 'array',
        'properties' => 'array',
        'created_at' => 'datetime',
    ];

    protected $dates = [
        'created_at',
    ];

    /**
     * Scope for filtering by property value
     */
    public function scopeForProperty($query, $key, $value)
    {
        return $query->whereJsonContains('properties->' . $key, $value);
    }

    /**
     * Scope for filtering by request
     */
    public function scopeForRequest($query, $requestId)
    {
        return $query->where('request_id', $requestId);
    }

    /**
     * Scope for filtering by level
     */
    public function scopeForLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Scope for filtering by date range
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope for recent entries
     */
    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>=', Carbon::now()->subHours($hours));
    }

    /**
     * Scope for oldest entries (for cleanup)
     */
    public function scopeOldest($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    /**
     * Get formatted context as JSON
     */
    public function getFormattedContextAttribute()
    {
        return json_encode($this->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get human readable time
     */
    public function getHumanTimeAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get log level with emoji
     */
    public function getLevelWithEmojiAttribute()
    {
        $emojis = [
            'debug' => '🐛',
            'info' => 'ℹ️',
            'notice' => '📝',
            'warning' => '⚠️',
            'error' => '❌',
            'critical' => '🚨',
            'alert' => '🚨',
            'emergency' => '🚨',
        ];

        return ($emojis[$this->level] ?? '📄') . ' ' . strtoupper($this->level);
    }

    /**
     * Get truncated message
     */
    public function getTruncatedMessageAttribute($length = 100)
    {
        return Str::limit($this->message, $length);
    }

    /**
     * Get property value from properties JSON
     */
    public function getProperty($key, $default = null)
    {
        return $this->properties[$key] ?? $default;
    }

    /**
     * Check if property exists
     */
    public function hasProperty($key)
    {
        return isset($this->properties[$key]);
    }

    /**
     * Get logs grouped by type
     */
    public static function getGroupedLogs($limit = 100)
    {
        return static::query()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->groupBy(function ($log) {
                return $log->context['type'] ?? 'unknown';
            });
    }

    /**
     * Get log statistics
     */
    public static function getStatistics($days = 7)
    {
        $query = static::query()
            ->where('created_at', '>=', Carbon::now()->subDays($days));

        // Count unique traces (request_ids) instead of individual logs
        $totalTraces = $query->select('request_id')->distinct()->count();

        // Get trace-level statistics by analyzing the latest log for each request
        $traceStats = $query->select('request_id', 'level', 'message')
            ->whereIn('id', function ($subQuery) use ($query) {
                $subQuery->selectRaw('MAX(id)')
                    ->from('log_entries')
                    ->whereIn('request_id', $query->select('request_id')->distinct())
                    ->groupBy('request_id');
            })
            ->get()
            ->groupBy('level')
            ->map->count()
            ->toArray();

        return [
            'total_logs' => $totalTraces, // This now represents traces, not individual logs
            'by_level' => $traceStats,
            'by_type' => $query->selectRaw('JSON_EXTRACT(context, "$.type") as type, COUNT(DISTINCT request_id) as count')
                ->whereNotNull('context')
                ->whereNotNull(DB::raw('JSON_EXTRACT(context, "$.type")'))
                ->groupBy(DB::raw('JSON_EXTRACT(context, "$.type")'))
                ->pluck('count', 'type'),
            'by_hour' => $query->selectRaw('HOUR(created_at) as hour, COUNT(DISTINCT request_id) as count')
                ->groupBy('hour')
                ->orderBy('hour')
                ->pluck('count', 'hour'),
            'by_controller' => $query->selectRaw('controller, COUNT(DISTINCT request_id) as count')
                ->whereNotNull('controller')
                ->groupBy('controller')
                ->pluck('count', 'controller'),
        ];
    }
}
