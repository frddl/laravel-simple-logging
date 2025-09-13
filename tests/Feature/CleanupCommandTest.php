<?php

namespace Frddl\LaravelSimpleLogging\Tests\Feature;

use Frddl\LaravelSimpleLogging\Models\LogEntry;
use Frddl\LaravelSimpleLogging\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class CleanupCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_cleanup_old_logs()
    {
        // Create old log entries (older than 7 days)
        $oldLogs = LogEntry::create([
            'request_id' => 'old-request-1',
            'level' => 'info',
            'message' => 'Old log entry',
            'context' => ['data' => 'old'],
            'properties' => ['test' => 'old'],
            'controller' => 'TestController',
            'method' => 'oldMethod',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
            'url' => 'http://test.com',
            'http_method' => 'GET',
            'created_at' => Carbon::now()->subDays(10),
        ]);

        // Create recent log entries (within 7 days)
        $recentLogs = LogEntry::create([
            'request_id' => 'recent-request-1',
            'level' => 'info',
            'message' => 'Recent log entry',
            'context' => ['data' => 'recent'],
            'properties' => ['test' => 'recent'],
            'controller' => 'TestController',
            'method' => 'recentMethod',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
            'url' => 'http://test.com',
            'http_method' => 'GET',
            'created_at' => Carbon::now()->subDays(3),
        ]);

        // Run cleanup command for logs older than 7 days
        $this->artisan('simple-logging:cleanup', ['--days' => 7])
            ->expectsOutput('Cleaning up log entries older than 7 days (before ' . Carbon::now()->subDays(7)->format('Y-m-d H:i:s') . ')...')
            ->expectsOutput('Successfully deleted 1 old log entries.')
            ->assertExitCode(0);

        // Assert old logs are deleted
        $this->assertDatabaseMissing('log_entries', [
            'request_id' => 'old-request-1',
        ]);

        // Assert recent logs are kept
        $this->assertDatabaseHas('log_entries', [
            'request_id' => 'recent-request-1',
        ]);
    }

    /** @test */
    public function it_uses_config_default_when_no_days_specified()
    {
        // Create old log entries (older than 30 days - config default)
        $oldLogs = LogEntry::create([
            'request_id' => 'old-request-2',
            'level' => 'info',
            'message' => 'Old log entry',
            'context' => ['data' => 'old'],
            'properties' => ['test' => 'old'],
            'controller' => 'TestController',
            'method' => 'oldMethod',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
            'url' => 'http://test.com',
            'http_method' => 'GET',
            'created_at' => Carbon::now()->subDays(35),
        ]);

        // Run cleanup command without specifying days
        $this->artisan('simple-logging:cleanup')
            ->expectsOutput('Cleaning up log entries older than 30 days (before ' . Carbon::now()->subDays(30)->format('Y-m-d H:i:s') . ')...')
            ->expectsOutput('Successfully deleted 1 old log entries.')
            ->assertExitCode(0);

        // Assert old logs are deleted
        $this->assertDatabaseMissing('log_entries', [
            'request_id' => 'old-request-2',
        ]);
    }

    /** @test */
    public function it_handles_edge_cases()
    {
        // Test that the command runs successfully with valid input
        $this->artisan('simple-logging:cleanup', ['--days' => 7])
            ->assertExitCode(0);
    }
}
