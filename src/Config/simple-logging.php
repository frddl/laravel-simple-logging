<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Simple Logging Settings
    |--------------------------------------------------------------------------
    |
    | Ultra-simple configuration for the SimpleLoggingTrait.
    |
    */

    'enabled' => env('LOGGING_TRAIT_ENABLED', false),
    'file_logging' => env('LOGGING_TRAIT_FILE_LOGGING', false),
    'database_logging' => env('LOGGING_TRAIT_DATABASE_LOGGING', true),
    'log_level' => env('LOGGING_TRAIT_LEVEL', 'info'),
    'viewer' => [
        'per_page' => 50,
    ],
    'export' => [
        'max_records' => 100,
    ],
    'cleanup_old_logs_days' => env('LOGGING_TRAIT_CLEANUP_DAYS', 5),
    'route_prefix' => env('SIMPLE_LOGGING_ROUTE_PREFIX', 'logs'),
    'middleware' => env('SIMPLE_LOGGING_MIDDLEWARE', 'api'),
];