<?php

namespace Frddl\LaravelSimpleLogging\Tests\Feature;

use Frddl\LaravelSimpleLogging\Models\LogEntry;
use Frddl\LaravelSimpleLogging\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NestedMethodCallsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Reset entry method before each test
        app()->forgetInstance('simple-logging.entry-method');
    }

    /** @test */
    public function it_tracks_first_method_when_methods_call_each_other()
    {
        $this->app['config']->set('simple-logging.enabled', true);
        $this->app['config']->set('simple-logging.database_logging', true);

        $controller = new NestedMethodTestController();
        
        // Start with methodA, which calls methodB, which calls methodC
        $controller->methodA();

        // All logs should have the same method (the first one: methodA)
        $logs = LogEntry::all();
        
        $this->assertCount(6, $logs); // 3 methods × 2 logs each (started + completed)
        
        // All logs should have the same method name (the first one)
        foreach ($logs as $log) {
            $this->assertEquals('methodA', $log->method, "Expected methodA but got {$log->method} for message: {$log->message}");
        }
    }

    /** @test */
    public function it_tracks_first_method_when_starting_from_different_entry_points()
    {
        $this->app['config']->set('simple-logging.enabled', true);
        $this->app['config']->set('simple-logging.database_logging', true);

        $controller = new NestedMethodTestController();
        
        // Start with methodB directly
        $controller->methodB();

        // All logs should have methodB as the entry method
        $logs = LogEntry::all();
        
        $this->assertCount(4, $logs); // 2 methods × 2 logs each (methodB + methodC)
        
        foreach ($logs as $log) {
            $this->assertEquals('methodB', $log->method, "Expected methodB but got {$log->method} for message: {$log->message}");
        }
    }

    /** @test */
    public function it_tracks_first_method_with_complex_nested_calls()
    {
        $this->app['config']->set('simple-logging.enabled', true);
        $this->app['config']->set('simple-logging.database_logging', true);

        $controller = new ComplexNestedTestController();
        
        // Start with processOrder, which calls multiple nested methods
        $controller->processOrder();

        // All logs should have processOrder as the entry method
        $logs = LogEntry::all();
        
        $this->assertCount(8, $logs); // 4 methods × 2 logs each
        
        foreach ($logs as $log) {
            $this->assertEquals('processOrder', $log->method, "Expected processOrder but got {$log->method} for message: {$log->message}");
        }
    }

    /** @test */
    public function it_handles_exceptions_in_nested_calls_correctly()
    {
        $this->app['config']->set('simple-logging.enabled', true);
        $this->app['config']->set('simple-logging.database_logging', true);

        $controller = new ExceptionNestedTestController();
        
        // This should throw an exception in methodC
        try {
            $controller->methodA();
        } catch (\Exception $e) {
            // Expected exception
        }

        // All logs should have methodA as the entry method, even the error log
        $logs = LogEntry::all();
        
        $this->assertCount(6, $logs); // 2 completed + 1 error + 3 started (methodA, methodB, methodC)
        
        foreach ($logs as $log) {
            $this->assertEquals('methodA', $log->method, "Expected methodA but got {$log->method} for message: {$log->message}");
        }
    }
}

class NestedMethodTestController
{
    use \Frddl\LaravelSimpleLogging\Traits\SimpleLoggingTrait;
    
    public function methodA()
    {
        return $this->logMethod('methodA', [], function() {
            $this->methodB();
            return 'Method A completed';
        });
    }
    
    public function methodB()
    {
        return $this->logMethod('methodB', [], function() {
            $this->methodC();
            return 'Method B completed';
        });
    }
    
    public function methodC()
    {
        return $this->logMethod('methodC', [], function() {
            return 'Method C completed';
        });
    }
}

class ComplexNestedTestController
{
    use \Frddl\LaravelSimpleLogging\Traits\SimpleLoggingTrait;
    
    public function processOrder()
    {
        return $this->logMethod('processOrder', [], function() {
            $this->validateOrder();
            $this->calculateTotal();
            $this->sendConfirmation();
            return 'Order processed successfully';
        });
    }
    
    private function validateOrder()
    {
        return $this->logMethod('validateOrder', [], function() {
            return 'Order validated';
        });
    }
    
    private function calculateTotal()
    {
        return $this->logMethod('calculateTotal', [], function() {
            return 'Total calculated';
        });
    }
    
    private function sendConfirmation()
    {
        return $this->logMethod('sendConfirmation', [], function() {
            return 'Confirmation sent';
        });
    }
}

class ExceptionNestedTestController
{
    use \Frddl\LaravelSimpleLogging\Traits\SimpleLoggingTrait;
    
    public function methodA()
    {
        return $this->logMethod('methodA', [], function() {
            $this->methodB();
            return 'Method A completed';
        });
    }
    
    public function methodB()
    {
        return $this->logMethod('methodB', [], function() {
            $this->methodC();
            return 'Method B completed';
        });
    }
    
    public function methodC()
    {
        return $this->logMethod('methodC', [], function() {
            throw new \Exception('Something went wrong in methodC');
        });
    }
}
