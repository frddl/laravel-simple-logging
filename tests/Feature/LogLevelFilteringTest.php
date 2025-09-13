<?php

namespace Frddl\LaravelSimpleLogging\Tests\Feature;

use Frddl\LaravelSimpleLogging\Models\LogEntry;
use Frddl\LaravelSimpleLogging\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LogLevelFilteringTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_respects_minimum_log_level_config()
    {
        // Set minimum log level to 'warning'
        $this->app['config']->set('simple-logging.log_level', 'warning');
        $this->app['config']->set('simple-logging.enabled', true);

        $controller = new LogLevelTestController();
        
        // These should not be logged (below warning level)
        $controller->logDebug('Debug message');
        $controller->logInfo('Info message');
        $controller->logNotice('Notice message');
        
        // These should be logged (warning level and above)
        $controller->logWarning('Warning message');
        $controller->logError('Error message');

        // Assert only warning and error logs were saved
        $this->assertDatabaseMissing('log_entries', [
            'level' => 'debug',
            'message' => 'Debug message'
        ]);
        
        $this->assertDatabaseMissing('log_entries', [
            'level' => 'info',
            'message' => 'Info message'
        ]);
        
        $this->assertDatabaseMissing('log_entries', [
            'level' => 'notice',
            'message' => 'Notice message'
        ]);
        
        $this->assertDatabaseHas('log_entries', [
            'level' => 'warning',
            'message' => 'Warning message'
        ]);
        
        $this->assertDatabaseHas('log_entries', [
            'level' => 'error',
            'message' => 'Error message'
        ]);
    }

    /** @test */
    public function it_respects_database_logging_config()
    {
        // Disable database logging
        $this->app['config']->set('simple-logging.database_logging', false);
        $this->app['config']->set('simple-logging.enabled', true);

        $controller = new LogLevelTestController();
        $controller->logInfo('Test message');

        // Assert no logs were saved to database
        $this->assertDatabaseMissing('log_entries', [
            'message' => 'Test message'
        ]);
    }

    /** @test */
    public function it_logs_to_database_when_enabled()
    {
        // Enable database logging
        $this->app['config']->set('simple-logging.database_logging', true);
        $this->app['config']->set('simple-logging.enabled', true);

        $controller = new LogLevelTestController();
        $controller->logInfo('Test message');

        // Assert log was saved to database
        $this->assertDatabaseHas('log_entries', [
            'message' => 'Test message',
            'level' => 'info'
        ]);
    }

    /** @test */
    public function it_logs_all_levels_when_minimum_is_debug()
    {
        // Set minimum log level to 'debug' (lowest level)
        $this->app['config']->set('simple-logging.log_level', 'debug');
        $this->app['config']->set('simple-logging.enabled', true);

        $controller = new LogLevelTestController();
        
        $controller->logDebug('Debug message');
        $controller->logInfo('Info message');
        $controller->logWarning('Warning message');
        $controller->logError('Error message');

        // Assert all logs were saved
        $this->assertDatabaseHas('log_entries', [
            'level' => 'debug',
            'message' => 'Debug message'
        ]);
        
        $this->assertDatabaseHas('log_entries', [
            'level' => 'info',
            'message' => 'Info message'
        ]);
        
        $this->assertDatabaseHas('log_entries', [
            'level' => 'warning',
            'message' => 'Warning message'
        ]);
        
        $this->assertDatabaseHas('log_entries', [
            'level' => 'error',
            'message' => 'Error message'
        ]);
    }
}

class LogLevelTestController
{
    use \Frddl\LaravelSimpleLogging\Traits\SimpleLoggingTrait;
    
    public function logDebug($message)
    {
        $this->log($message, [], 'debug');
    }
    
    public function logInfo($message)
    {
        $this->log($message, [], 'info');
    }
    
    public function logNotice($message)
    {
        $this->log($message, [], 'notice');
    }
    
    public function logWarning($message)
    {
        $this->log($message, [], 'warning');
    }
    
    public function logError($message)
    {
        $this->log($message, [], 'error');
    }
}
