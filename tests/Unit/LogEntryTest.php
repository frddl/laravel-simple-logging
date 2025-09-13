<?php

namespace Frddl\LaravelSimpleLogging\Tests\Unit;

use Frddl\LaravelSimpleLogging\Models\LogEntry;
use Frddl\LaravelSimpleLogging\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LogEntryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_log_entry()
    {
        $logEntry = LogEntry::create([
            'request_id' => 'test-123',
            'level' => 'info',
            'message' => 'Test message',
            'context' => ['key' => 'value'],
            'properties' => ['data' => 'test'],
            'controller' => 'TestController',
            'method' => 'testMethod',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
            'url' => 'http://test.com',
            'http_method' => 'GET',
            'created_at' => now(),
        ]);

        $this->assertDatabaseHas('log_entries', [
            'request_id' => 'test-123',
            'level' => 'info',
            'message' => 'Test message',
        ]);
    }

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        $logEntry = new LogEntry();
        
        $expectedFillable = [
            'request_id',
            'level',
            'message',
            'context',
            'properties',
            'controller',
            'method',
            'ip_address',
            'user_agent',
            'url',
            'http_method',
            'created_at',
        ];

        $this->assertEquals($expectedFillable, $logEntry->getFillable());
    }
}
