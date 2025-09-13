<?php

namespace Frddl\LaravelSimpleLogging\Tests\Feature;

use Frddl\LaravelSimpleLogging\Models\LogEntry;
use Frddl\LaravelSimpleLogging\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SimpleLoggingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_log_messages()
    {
        $this->app['config']->set('simple-logging.enabled', true);
        
        $controller = new TestController();
        $controller->testMethod();
        
        $this->assertDatabaseHas('log_entries', [
            'message' => 'Test Method started',
            'level' => 'info'
        ]);
        
        $this->assertDatabaseHas('log_entries', [
            'message' => 'Test Method completed',
            'level' => 'info'
        ]);
    }

    /** @test */
    public function it_handles_exceptions()
    {
        $this->app['config']->set('simple-logging.enabled', true);
        
        $controller = new TestController();
        
        try {
            $controller->failingMethod();
        } catch (\Exception $e) {
            // Expected exception
        }
        
        $this->assertDatabaseHas('log_entries', [
            'message' => 'Failing Method failed',
            'level' => 'error'
        ]);
    }

    /** @test */
    public function it_respects_enabled_config()
    {
        $this->app['config']->set('simple-logging.enabled', false);
        
        $controller = new TestController();
        $controller->testMethod();
        
        $this->assertDatabaseMissing('log_entries', [
            'message' => 'Test Method started'
        ]);
    }
}

class TestController
{
    use \Frddl\LaravelSimpleLogging\Traits\SimpleLoggingTrait;
    
    public function testMethod()
    {
        return $this->logMethod('Test Method', [], function() {
            return 'success';
        });
    }
    
    public function failingMethod()
    {
        return $this->logMethod('Failing Method', [], function() {
            throw new \Exception('Test exception');
        });
    }
}
