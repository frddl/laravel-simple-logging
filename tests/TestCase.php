<?php

namespace Frddl\LaravelSimpleLogging\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Frddl\LaravelSimpleLogging\SimpleLoggingServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->loadMigrationsFrom(__DIR__.'/../src/Database/Migrations');
    }

    protected function getPackageProviders($app)
    {
        return [
            SimpleLoggingServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
