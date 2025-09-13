<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Simple Logging Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the Laravel Simple Logging package. This package provides
    | elegant application flow monitoring with comprehensive logging of warnings,
    | infos, debug variables, and performance metrics.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Enable/Disable Logging
    |--------------------------------------------------------------------------
    |
    | Set to false to completely disable all logging functionality.
    | This is useful for production environments where you want to turn off
    | logging without removing the code.
    |
    | Environment Variable: SIMPLE_LOGGING_ENABLED
    | Default: true
    |
    */
    'enabled' => env('SIMPLE_LOGGING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | File Logging
    |--------------------------------------------------------------------------
    |
    | Enable or disable file logging in addition to database logging.
    | When enabled, logs will be written to both the database and Laravel's
    | default log files using the standard Log facade.
    |
    | Environment Variable: SIMPLE_LOGGING_FILE_LOGGING
    | Default: false
    |
    */
    'file_logging' => env('SIMPLE_LOGGING_FILE_LOGGING', false),

    /*
    |--------------------------------------------------------------------------
    | Database Logging
    |--------------------------------------------------------------------------
    |
    | Enable or disable database logging. When disabled, logs will only be
    | written to files (if file_logging is enabled) or not at all.
    |
    | Environment Variable: SIMPLE_LOGGING_DATABASE_LOGGING
    | Default: true
    |
    */
    'database_logging' => env('SIMPLE_LOGGING_DATABASE_LOGGING', true),

    /*
    |--------------------------------------------------------------------------
    | Minimum Log Level
    |--------------------------------------------------------------------------
    |
    | Set the minimum log level to save. Only logs at or above this level
    | will be stored. This helps reduce noise by filtering out debug logs
    | in production environments.
    |
    | Available levels: debug, info, notice, warning, error, critical, alert, emergency
    |
    | Environment Variable: SIMPLE_LOGGING_LEVEL
    | Default: info
    |
    */
    'log_level' => env('SIMPLE_LOGGING_LEVEL', 'info'),

    /*
    |--------------------------------------------------------------------------
    | Web Interface Settings
    |--------------------------------------------------------------------------
    |
    | Configure the web interface for viewing and managing logs.
    |
    */
    'viewer' => [
        /*
        |--------------------------------------------------------------------------
        | Logs Per Page
        |--------------------------------------------------------------------------
        |
        | Number of log entries to display per page in the web interface.
        | This affects both the main log viewer and API responses.
        |
        | Default: 50
        |
        */
        'per_page' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Settings
    |--------------------------------------------------------------------------
    |
    | Configure log export functionality for downloading logs in various formats.
    |
    */
    'export' => [
        /*
        |--------------------------------------------------------------------------
        | Maximum Export Records
        |--------------------------------------------------------------------------
        |
        | Maximum number of log entries that can be exported in a single request.
        | This prevents memory issues when exporting large amounts of data.
        |
        | Default: 1000
        |
        */
        'max_records' => 1000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Cleanup Settings
    |--------------------------------------------------------------------------
    |
    | Configure automatic cleanup of old log entries to prevent database bloat.
    | The package includes a scheduled task that runs daily to clean up old logs.
    |
    | Set to null or 0 to disable automatic cleanup.
    |
    | Environment Variable: SIMPLE_LOGGING_CLEANUP_DAYS
    | Default: 30
    |
    */
    'cleanup_old_logs_days' => env('SIMPLE_LOGGING_CLEANUP_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Web Interface Routes
    |--------------------------------------------------------------------------
    |
    | Configure the web interface routes for viewing and managing logs.
    |
    */
    'route_prefix' => env('SIMPLE_LOGGING_ROUTE_PREFIX', 'logs'),

    /*
    |--------------------------------------------------------------------------
    | Web Interface Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware to apply to the web interface routes. This allows you to
    | protect the log viewer with authentication, authorization, or other
    | middleware as needed.
    |
    | Can be a string or array of middleware names.
    |
    | Environment Variable: SIMPLE_LOGGING_MIDDLEWARE
    | Default: ['web']
    |
    | Examples:
    | 'middleware' => ['web', 'auth']
    | 'middleware' => ['web', 'auth', 'can:view-logs']
    | 'middleware' => 'web'
    |
    */
    'middleware' => env('SIMPLE_LOGGING_MIDDLEWARE', ['web']),
];
