<?php

namespace Frddl\LaravelSimpleLogging;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class SimpleLoggingServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        // Publish config file
        $this->publishes([
            __DIR__.'/Config/simple-logging.php' => config_path('simple-logging.php'),
        ], 'config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/Database/Migrations' => database_path('migrations'),
        ], 'migrations');

        // Publish views
        $this->publishes([
            __DIR__.'/Resources/views' => resource_path('views/vendor/simple-logging'),
        ], 'views');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        // Load views
        $this->loadViewsFrom(__DIR__.'/Resources/views', 'simple-logging');

        // Register routes
        $this->registerRoutes();
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__.'/Config/simple-logging.php',
            'simple-logging'
        );
    }

    /**
     * Register the package routes.
     */
    protected function registerRoutes()
    {
        $middleware = config('simple-logging.middleware', []);

        // Handle both string and array middleware configurations
        if (is_string($middleware)) {
            $middleware = [$middleware];
        }

        Route::group([
            'prefix' => config('simple-logging.route_prefix', 'logs'),
            'middleware' => $middleware,
        ], function () {
            Route::get('/', [\Frddl\LaravelSimpleLogging\Http\Controllers\LogViewerController::class, 'index'])->name('simple-logging.index');
            Route::get('/api', [\Frddl\LaravelSimpleLogging\Http\Controllers\LogViewerController::class, 'getLogs'])->name('simple-logging.api');
            Route::get('/api/statistics', [\Frddl\LaravelSimpleLogging\Http\Controllers\LogViewerController::class, 'getStatistics'])->name('simple-logging.statistics');
            Route::get('/api/property-keys', [\Frddl\LaravelSimpleLogging\Http\Controllers\LogViewerController::class, 'getPropertyKeys'])->name('simple-logging.property-keys');
        });
    }
}
