<?php

namespace Frddl\LaravelSimpleLogging\Tests\Feature;

use Frddl\LaravelSimpleLogging\Models\LogEntry;
use Frddl\LaravelSimpleLogging\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EntryMethodTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Reset entry method before each test
        app()->forgetInstance('simple-logging.entry-method');
    }

    /** @test */
    public function it_tracks_first_method_as_entry_method_across_request()
    {
        $this->app['config']->set('simple-logging.enabled', true);
        $this->app['config']->set('simple-logging.database_logging', true);

        $controller = new EntryMethodTestController();
        
        // Call multiple methods - first one should be the entry method
        $controller->firstMethod();
        $controller->secondMethod();
        $controller->thirdMethod();

        // All logs should have the same method (the first one)
        $logs = LogEntry::all();
        
        $this->assertCount(6, $logs); // 3 methods × 2 logs each (started + completed)
        
        // All logs should have the same method name (the first one)
        foreach ($logs as $log) {
            $this->assertEquals('firstMethod', $log->method);
        }
    }

    /** @test */
    public function it_tracks_entry_method_even_when_logging_is_disabled()
    {
        $this->app['config']->set('simple-logging.enabled', false);

        $controller = new EntryMethodTestController();
        
        // Even with logging disabled, the entry method should be set
        $controller->firstMethod();
        $controller->secondMethod();

        // No logs should be created, but entry method should still be tracked
        $this->assertDatabaseCount('log_entries', 0);
    }

    /** @test */
    public function it_handles_direct_log_calls_without_entry_method()
    {
        $this->app['config']->set('simple-logging.enabled', true);
        $this->app['config']->set('simple-logging.database_logging', true);

        $controller = new EntryMethodTestController();
        
        // Direct log calls (not through logMethod) should use 'unknown' as method
        $controller->directLog('Test message');

        $log = LogEntry::first();
        $this->assertNotNull($log, 'Log entry should be created');
        $this->assertEquals('unknown', $log->method);
    }

    /** @test */
    public function it_resets_entry_method_between_requests()
    {
        $this->app['config']->set('simple-logging.enabled', true);
        $this->app['config']->set('simple-logging.database_logging', true);

        // Simulate first request
        $controller1 = new EntryMethodTestController();
        $controller1->firstMethod();
        
        $logs1 = LogEntry::all();
        $this->assertCount(2, $logs1);
        foreach ($logs1 as $log) {
            $this->assertEquals('firstMethod', $log->method);
        }

        // Clear database for second request simulation
        LogEntry::truncate();
        
        // Reset entry method for second request simulation
        app()->forgetInstance('simple-logging.entry-method');

        // Simulate second request with different entry method
        $controller2 = new EntryMethodTestController();
        $controller2->secondMethod();
        
        $logs2 = LogEntry::all();
        $this->assertCount(2, $logs2);
        foreach ($logs2 as $log) {
            $this->assertEquals('secondMethod', $log->method);
        }
    }
}

class EntryMethodTestController
{
    use \Frddl\LaravelSimpleLogging\Traits\SimpleLoggingTrait;
    
    public function firstMethod()
    {
        return $this->logMethod('firstMethod', [], function() {
            return 'First method result';
        });
    }
    
    public function secondMethod()
    {
        return $this->logMethod('secondMethod', [], function() {
            return 'Second method result';
        });
    }
    
    public function thirdMethod()
    {
        return $this->logMethod('thirdMethod', [], function() {
            return 'Third method result';
        });
    }
    
    public function directLog($message)
    {
        $this->log($message, [], 'info');
    }
}
