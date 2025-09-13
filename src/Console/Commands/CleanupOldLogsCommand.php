<?php

namespace Frddl\LaravelSimpleLogging\Console\Commands;

use Carbon\Carbon;
use Frddl\LaravelSimpleLogging\Models\LogEntry;
use Illuminate\Console\Command;

class CleanupOldLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'simple-logging:cleanup {--days= : Number of days to keep logs (overrides config)}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up old log entries based on the configured retention period';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days') ?: config('simple-logging.cleanup_old_logs_days', 30);

        if (! is_numeric($days) || $days < 1) {
            $this->error('Days must be a positive number.');

            return 1;
        }

        $cutoffDate = Carbon::now()->subDays($days);

        $this->info("Cleaning up log entries older than {$days} days (before {$cutoffDate->format('Y-m-d H:i:s')})...");

        $deletedCount = LogEntry::where('created_at', '<', $cutoffDate)->delete();

        $this->info("Successfully deleted {$deletedCount} old log entries.");

        return 0;
    }
}
