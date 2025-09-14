<?php

namespace Frddl\LaravelSimpleLogging\Console\Commands;

use Frddl\LaravelSimpleLogging\Traits\SimpleLoggingTrait;
use Illuminate\Console\Command;

class TestLoggingCommand extends Command
{
    use SimpleLoggingTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'simple-logging:test 
                            {--comprehensive : Run comprehensive test with all features}
                            {--quick : Run quick test with basic features}
                            {--clean : Clean up test logs after running}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the Laravel Simple Logging system with various scenarios';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting Laravel Simple Logging System Test...');
        $this->newLine();

        if ($this->option('comprehensive')) {
            $this->runComprehensiveTest();
        } elseif ($this->option('quick')) {
            $this->runQuickTest();
        } else {
            $this->runComprehensiveTest();
        }

        if ($this->option('clean')) {
            $this->cleanupTestLogs();
        }

        $this->newLine();
        $this->info('✅ Test completed successfully!');
        $this->info('🔍 Check the log viewer at /ac/logs to see the results.');
    }

    /**
     * Run comprehensive test with all features
     */
    private function runComprehensiveTest()
    {
        $this->info('📊 Running Comprehensive Logging Test...');

        return $this->logMethod('Comprehensive Logging Test', [
            'test_type' => 'comprehensive_cli_test',
            'timestamp' => now()->toISOString(),
            'command' => 'artisan simple-logging:test --comprehensive',
            'user' => get_current_user(),
        ], function () {

            // Test 1: Different log levels
            $this->logInfo('Info level log with basic data', [
                'message' => 'This is an info level log',
                'category' => 'Information',
                'data' => ['key1' => 'value1', 'key2' => 'value2'],
            ]);

            $this->logWarning('Warning level log with important data', [
                'message' => 'This is a warning level log',
                'category' => 'Warning',
                'warning_type' => 'deprecation',
                'data' => ['old_feature' => 'deprecated', 'new_feature' => 'recommended'],
            ]);

            $this->logError('Error level log with error details', [
                'message' => 'This is an error level log',
                'category' => 'Error',
                'error_code' => 'E001',
                'error_message' => 'Something went wrong',
                'data' => ['error_context' => 'test_context', 'stack_trace' => 'fake_stack_trace'],
            ]);

            $this->logDebug('Debug level log with detailed information', [
                'message' => 'This is a debug level log',
                'category' => 'Debug',
                'debug_info' => 'Detailed debugging information',
                'data' => ['debug_context' => 'test_debug', 'variables' => ['var1' => 'value1', 'var2' => 'value2']],
            ]);

            $this->logSuccess('Success level log with positive outcome', [
                'message' => 'This is a success level log',
                'category' => 'Success',
                'success_type' => 'operation_completed',
                'data' => ['result' => 'success', 'performance' => 'excellent'],
            ]);

            $this->logCritical('Critical level log with urgent information', [
                'message' => 'This is a critical level log',
                'category' => 'Critical',
                'critical_type' => 'system_failure',
                'data' => ['system_component' => 'database', 'failure_reason' => 'connection_lost'],
            ]);

            $this->logAlert('Alert level log with immediate attention needed', [
                'message' => 'This is an alert level log',
                'category' => 'Alert',
                'alert_type' => 'security_breach',
                'data' => ['security_level' => 'high', 'threat_type' => 'unauthorized_access'],
            ]);

            $this->logEmergency('Emergency level log with system-wide impact', [
                'message' => 'This is an emergency level log',
                'category' => 'Emergency',
                'emergency_type' => 'system_down',
                'data' => ['impact' => 'system_wide', 'recovery_time' => 'unknown'],
            ]);

            // Test 2: Empty and non-empty input data
            $this->logInfo('Log with empty input data', [
                'message' => 'This log has empty input data',
                'category' => 'Data Test',
                'input_data' => [],
                'data' => ['empty_array' => [], 'null_value' => null, 'empty_string' => ''],
            ]);

            $this->logInfo('Log with non-empty input data', [
                'message' => 'This log has non-empty input data',
                'category' => 'Data Test',
                'input_data' => [
                    'user_id' => 123,
                    'username' => 'test_user',
                    'email' => 'test@example.com',
                    'preferences' => [
                        'theme' => 'dark',
                        'language' => 'en',
                        'notifications' => true,
                    ],
                ],
                'data' => ['complex_data' => 'present', 'nested_objects' => 'included'],
            ]);

            // Test 3: Different data types and structures
            $this->logInfo('Log with various data types', [
                'message' => 'This log demonstrates various data types',
                'category' => 'Data Types',
                'data' => [
                    'string' => 'This is a string',
                    'integer' => 42,
                    'float' => 3.14159,
                    'boolean' => true,
                    'array' => [1, 2, 3, 'four', 'five'],
                    'associative_array' => [
                        'key1' => 'value1',
                        'key2' => 'value2',
                        'nested' => [
                            'deep_key' => 'deep_value',
                            'numbers' => [1, 2, 3],
                        ],
                    ],
                    'null_value' => null,
                    'empty_string' => '',
                    'empty_array' => [],
                ],
            ]);

            // Test 4: Performance metrics
            $this->logInfo('Log with performance metrics', [
                'message' => 'This log includes performance metrics',
                'category' => 'Performance',
                'data' => [
                    'duration_ms' => 150,
                    'memory_used' => '2.5MB',
                    'cpu_usage' => '15%',
                    'database_queries' => 5,
                    'cache_hits' => 12,
                    'cache_misses' => 3,
                ],
            ]);

            // Test 5: Different categories
            $this->logInfo('Authentication category log', [
                'message' => 'User authentication attempt',
                'category' => 'Authentication',
                'data' => ['user_id' => 123, 'auth_method' => 'password'],
            ]);

            $this->logInfo('Database category log', [
                'message' => 'Database query executed',
                'category' => 'Database',
                'data' => ['query_type' => 'SELECT', 'table' => 'users', 'execution_time' => '50ms'],
            ]);

            $this->logInfo('API category log', [
                'message' => 'External API call made',
                'category' => 'API',
                'data' => ['endpoint' => 'https://api.example.com/data', 'method' => 'GET', 'status_code' => 200],
            ]);

            $this->logInfo('Security category log', [
                'message' => 'Security event detected',
                'category' => 'Security',
                'data' => ['event_type' => 'login_attempt', 'ip_address' => '192.168.1.100', 'risk_level' => 'medium'],
            ]);

            // Test 6: Nested logging (call depth)
            $this->logInfo('Starting nested function calls', [
                'message' => 'This will demonstrate nested logging',
                'category' => 'Nested Calls',
            ]);

            $this->nestedFunctionCall1();

            // Test 7: Different status codes simulation with actual Laravel responses
            $this->logInfo('Simulating different HTTP status codes', [
                'message' => 'This will demonstrate different status codes',
                'category' => 'Status Codes',
            ]);

            // Test with actual Laravel Response objects to capture status codes and content types
            $this->testHttpResponses();

            // Test 8: Complex nested data structures with all permutations
            $this->logInfo('Log with complex nested data structures', [
                'message' => 'This log demonstrates complex nested data with all data type permutations',
                'category' => 'Complex Data',
                'data' => [
                    'user_profile' => [
                        'personal_info' => [
                            'name' => 'John Doe',
                            'age' => 30,
                            'email' => 'john@example.com',
                            'address' => [
                                'street' => '123 Main St',
                                'city' => 'New York',
                                'state' => 'NY',
                                'zip' => '10001',
                                'country' => 'USA',
                                'coordinates' => [
                                    'latitude' => 40.7128,
                                    'longitude' => -74.0060,
                                ],
                            ],
                            'phone_numbers' => [
                                'primary' => '+1-555-0123',
                                'secondary' => '+1-555-0456',
                                'emergency' => '+1-555-0789',
                            ],
                        ],
                        'preferences' => [
                            'theme' => 'dark',
                            'language' => 'en',
                            'timezone' => 'America/New_York',
                            'notifications' => [
                                'email' => true,
                                'sms' => false,
                                'push' => true,
                                'frequency' => 'daily',
                                'quiet_hours' => [
                                    'start' => '22:00',
                                    'end' => '08:00',
                                ],
                            ],
                            'privacy' => [
                                'profile_visibility' => 'public',
                                'data_sharing' => false,
                                'analytics' => true,
                            ],
                        ],
                        'activity' => [
                            'last_login' => now()->subHours(1)->toISOString(),
                            'login_count' => 42,
                            'sessions' => [
                                [
                                    'id' => 'sess_1',
                                    'started' => now()->subHours(2)->toISOString(),
                                    'ip_address' => '192.168.1.100',
                                    'user_agent' => 'Mozilla/5.0...',
                                    'active' => false,
                                ],
                                [
                                    'id' => 'sess_2',
                                    'started' => now()->subHours(1)->toISOString(),
                                    'ip_address' => '192.168.1.101',
                                    'user_agent' => 'Mozilla/5.0...',
                                    'active' => true,
                                ],
                            ],
                            'actions' => [
                                'page_views' => 156,
                                'api_calls' => 89,
                                'downloads' => 12,
                            ],
                        ],
                    ],
                    'system_metrics' => [
                        'performance' => [
                            'response_time' => 150.75,
                            'memory_usage' => '2.5MB',
                            'cpu_usage' => 15.3,
                            'disk_usage' => '45.2GB',
                            'network_io' => [
                                'bytes_sent' => 1024000,
                                'bytes_received' => 2048000,
                                'packets_sent' => 1500,
                                'packets_received' => 2300,
                            ],
                        ],
                        'database' => [
                            'queries_executed' => 25,
                            'slow_queries' => 2,
                            'connection_pool' => [
                                'active' => 5,
                                'idle' => 10,
                                'max' => 20,
                                'waiting' => 0,
                            ],
                            'tables' => [
                                'users' => [
                                    'rows' => 1000,
                                    'size' => '2.1MB',
                                    'indexes' => 3,
                                ],
                                'orders' => [
                                    'rows' => 5000,
                                    'size' => '15.7MB',
                                    'indexes' => 5,
                                ],
                            ],
                        ],
                        'cache' => [
                            'hits' => 1200,
                            'misses' => 300,
                            'hit_rate' => 0.8,
                            'memory_used' => '128MB',
                            'keys' => [
                                'user:123' => 'cached',
                                'session:abc' => 'cached',
                                'config:app' => 'cached',
                            ],
                        ],
                    ],
                    'business_logic' => [
                        'orders' => [
                            'total_orders' => 150,
                            'total_revenue' => 25000.50,
                            'average_order_value' => 166.67,
                            'order_statuses' => [
                                'pending' => 5,
                                'processing' => 12,
                                'shipped' => 45,
                                'delivered' => 88,
                                'cancelled' => 0,
                            ],
                            'recent_orders' => [
                                [
                                    'id' => 'ORD-001',
                                    'customer_id' => 123,
                                    'amount' => 99.99,
                                    'status' => 'delivered',
                                    'items' => [
                                        ['name' => 'Product A', 'quantity' => 2, 'price' => 49.99],
                                        ['name' => 'Product B', 'quantity' => 1, 'price' => 0.01],
                                    ],
                                    'created_at' => now()->subDays(1)->toISOString(),
                                ],
                                [
                                    'id' => 'ORD-002',
                                    'customer_id' => 456,
                                    'amount' => 149.50,
                                    'status' => 'shipped',
                                    'items' => [
                                        ['name' => 'Product C', 'quantity' => 1, 'price' => 149.50],
                                    ],
                                    'created_at' => now()->subHours(6)->toISOString(),
                                ],
                            ],
                        ],
                        'inventory' => [
                            'total_products' => 500,
                            'low_stock_threshold' => 10,
                            'low_stock_items' => [
                                'PROD-001' => 5,
                                'PROD-002' => 3,
                                'PROD-003' => 8,
                            ],
                            'categories' => [
                                'electronics' => [
                                    'count' => 150,
                                    'value' => 50000.00,
                                    'top_selling' => 'PROD-001',
                                ],
                                'clothing' => [
                                    'count' => 200,
                                    'value' => 25000.00,
                                    'top_selling' => 'PROD-002',
                                ],
                                'books' => [
                                    'count' => 150,
                                    'value' => 15000.00,
                                    'top_selling' => 'PROD-003',
                                ],
                            ],
                        ],
                    ],
                    'api_responses' => [
                        'external_apis' => [
                            'payment_gateway' => [
                                'status' => 'healthy',
                                'response_time' => 250,
                                'success_rate' => 0.99,
                                'last_check' => now()->subMinutes(5)->toISOString(),
                                'endpoints' => [
                                    'charge' => ['calls' => 1000, 'errors' => 5],
                                    'refund' => ['calls' => 50, 'errors' => 0],
                                    'void' => ['calls' => 25, 'errors' => 1],
                                ],
                            ],
                            'shipping_api' => [
                                'status' => 'degraded',
                                'response_time' => 1200,
                                'success_rate' => 0.95,
                                'last_check' => now()->subMinutes(2)->toISOString(),
                                'endpoints' => [
                                    'calculate' => ['calls' => 500, 'errors' => 25],
                                    'track' => ['calls' => 200, 'errors' => 10],
                                ],
                            ],
                        ],
                        'internal_apis' => [
                            'user_service' => [
                                'status' => 'healthy',
                                'response_time' => 50,
                                'success_rate' => 0.999,
                                'version' => '2.1.0',
                            ],
                            'order_service' => [
                                'status' => 'healthy',
                                'response_time' => 75,
                                'success_rate' => 0.998,
                                'version' => '1.5.2',
                            ],
                        ],
                    ],
                    'error_tracking' => [
                        'recent_errors' => [
                            [
                                'id' => 'ERR-001',
                                'type' => 'ValidationError',
                                'message' => 'Invalid email format',
                                'count' => 5,
                                'first_seen' => now()->subHours(2)->toISOString(),
                                'last_seen' => now()->subMinutes(30)->toISOString(),
                                'stack_trace' => 'at validateEmail() line 123...',
                            ],
                            [
                                'id' => 'ERR-002',
                                'type' => 'DatabaseError',
                                'message' => 'Connection timeout',
                                'count' => 2,
                                'first_seen' => now()->subHours(1)->toISOString(),
                                'last_seen' => now()->subMinutes(15)->toISOString(),
                                'stack_trace' => 'at connect() line 456...',
                            ],
                        ],
                        'error_rates' => [
                            'last_hour' => 0.01,
                            'last_day' => 0.005,
                            'last_week' => 0.002,
                        ],
                    ],
                    'security_events' => [
                        'failed_logins' => [
                            'count' => 3,
                            'last_attempt' => now()->subMinutes(10)->toISOString(),
                            'ip_addresses' => ['192.168.1.200', '10.0.0.15'],
                        ],
                        'suspicious_activity' => [
                            'count' => 1,
                            'type' => 'unusual_location',
                            'details' => 'Login from new country',
                            'timestamp' => now()->subHours(1)->toISOString(),
                        ],
                    ],
                ],
            ]);

            // Test 9: Nested structures with data type permutations
            $this->logInfo('Log with nested data type permutations', [
                'message' => 'This log demonstrates nested structures with all possible data type combinations',
                'category' => 'Data Permutations',
                'data' => [
                    'mixed_arrays' => [
                        'string_array' => ['a', 'b', 'c', 'd', 'e'],
                        'integer_array' => [1, 2, 3, 4, 5],
                        'float_array' => [1.1, 2.2, 3.3, 4.4, 5.5],
                        'boolean_array' => [true, false, true, false, true],
                        'mixed_array' => ['string', 123, 45.67, true, null, ['nested' => 'array']],
                        'empty_array' => [],
                        'null_array' => [null, null, null],
                    ],
                    'nested_objects' => [
                        'level_1' => [
                            'string' => 'level 1 string',
                            'number' => 100,
                            'boolean' => true,
                            'null_value' => null,
                            'level_2' => [
                                'string' => 'level 2 string',
                                'number' => 200,
                                'boolean' => false,
                                'null_value' => null,
                                'level_3' => [
                                    'string' => 'level 3 string',
                                    'number' => 300,
                                    'boolean' => true,
                                    'null_value' => null,
                                    'level_4' => [
                                        'deepest' => 'This is 4 levels deep',
                                        'array_in_object' => [1, 2, 3],
                                        'object_in_array' => [
                                            ['key' => 'value1'],
                                            ['key' => 'value2'],
                                            ['key' => 'value3'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'complex_combinations' => [
                        'array_of_objects' => [
                            [
                                'id' => 1,
                                'name' => 'Item 1',
                                'active' => true,
                                'price' => 99.99,
                                'tags' => ['tag1', 'tag2', 'tag3'],
                                'metadata' => [
                                    'created_at' => '2023-01-01T00:00:00Z',
                                    'updated_at' => '2023-01-02T00:00:00Z',
                                    'deleted_at' => null,
                                ],
                            ],
                            [
                                'id' => 2,
                                'name' => 'Item 2',
                                'active' => false,
                                'price' => 149.50,
                                'tags' => ['tag4', 'tag5'],
                                'metadata' => [
                                    'created_at' => '2023-01-03T00:00:00Z',
                                    'updated_at' => '2023-01-04T00:00:00Z',
                                    'deleted_at' => '2023-01-05T00:00:00Z',
                                ],
                            ],
                        ],
                        'object_with_arrays' => [
                            'users' => [
                                ['id' => 1, 'name' => 'John', 'age' => 30],
                                ['id' => 2, 'name' => 'Jane', 'age' => 25],
                                ['id' => 3, 'name' => 'Bob', 'age' => 35],
                            ],
                            'products' => [
                                ['id' => 'P001', 'name' => 'Product A', 'price' => 19.99],
                                ['id' => 'P002', 'name' => 'Product B', 'price' => 29.99],
                                ['id' => 'P003', 'name' => 'Product C', 'price' => 39.99],
                            ],
                            'categories' => [
                                ['id' => 1, 'name' => 'Electronics', 'parent_id' => null],
                                ['id' => 2, 'name' => 'Phones', 'parent_id' => 1],
                                ['id' => 3, 'name' => 'Laptops', 'parent_id' => 1],
                            ],
                        ],
                        'mixed_data_types' => [
                            'strings' => [
                                'empty' => '',
                                'single_char' => 'a',
                                'short' => 'hello',
                                'long' => str_repeat('This is a very long string. ', 50),
                                'unicode' => 'Hello 世界 🌍 🚀 💻',
                                'special_chars' => '!@#$%^&*()_+-=[]{}|;:,.<>?',
                                'html' => '<div>Hello <strong>World</strong></div>',
                                'json' => '{"key": "value", "nested": {"array": [1, 2, 3]}}',
                            ],
                            'numbers' => [
                                'zero' => 0,
                                'positive_int' => 42,
                                'negative_int' => -42,
                                'positive_float' => 3.14159,
                                'negative_float' => -3.14159,
                                'very_small' => 0.000001,
                                'very_large' => 999999999,
                                'scientific' => 1.23e-4,
                            ],
                            'booleans' => [
                                'true' => true,
                                'false' => false,
                            ],
                            'nulls' => [
                                'null' => null,
                            ],
                        ],
                    ],
                    'edge_cases' => [
                        'empty_values' => [
                            'empty_string' => '',
                            'empty_array' => [],
                            'empty_object' => (object)[],
                            'null_value' => null,
                            'zero' => 0,
                            'false' => false,
                        ],
                        'special_strings' => [
                            'whitespace' => '   ',
                            'newlines' => "line1\nline2\nline3",
                            'tabs' => "col1\tcol2\tcol3",
                            'quotes' => 'He said "Hello" and \'Goodbye\'',
                            'backslashes' => 'C:\\Users\\Name\\Documents',
                            'unicode_emoji' => '😀😁😂🤣😃😄😅😆😉😊',
                        ],
                        'boundary_values' => [
                            'max_int' => PHP_INT_MAX,
                            'min_int' => PHP_INT_MIN,
                            'max_float' => PHP_FLOAT_MAX,
                            'min_float' => PHP_FLOAT_MIN,
                            'infinity' => INF,
                            'negative_infinity' => -INF,
                            'nan' => NAN,
                        ],
                    ],
                ],
            ]);

            // Test 10: Edge cases and special characters
            $this->logInfo('Log with special characters and edge cases', [
                'message' => 'This log tests special characters and edge cases',
                'category' => 'Edge Cases',
                'data' => [
                    'special_chars' => '!@#$%^&*()_+-=[]{}|;:,.<>?',
                    'unicode' => 'Hello 世界 🌍',
                    'html_entities' => '&lt;script&gt;alert("test")&lt;/script&gt;',
                    'json_string' => '{"key": "value", "nested": {"array": [1, 2, 3]}}',
                ],
            ]);

            // Test 11: Final completion log
            $this->logSuccess('Comprehensive test completed successfully', [
                'message' => 'All test variations have been logged',
                'category' => 'Test Completion',
                'data' => [
                    'total_logs' => 26,
                    'test_duration' => 'completed',
                    'all_variations_tested' => true,
                    'nested_structures_tested' => true,
                    'data_permutations_tested' => true,
                ],
            ]);

            // Return comprehensive response data
            return [
                'message' => 'Comprehensive logging test completed successfully',
                'status' => 'success',
                'test_details' => [
                    'total_logs_generated' => 26,
                    'log_levels_tested' => ['info', 'warning', 'error', 'debug', 'success', 'critical', 'alert', 'emergency'],
                    'data_types_tested' => ['string', 'integer', 'float', 'boolean', 'array', 'object', 'null'],
                    'status_codes_tested' => [200, 400, 500],
                    'categories_tested' => ['Information', 'Warning', 'Error', 'Debug', 'Success', 'Critical', 'Alert', 'Emergency', 'Data Test', 'Data Types', 'Performance', 'Authentication', 'Database', 'API', 'Security', 'Nested Calls', 'Status Codes', 'Complex Data', 'Data Permutations', 'Edge Cases', 'Test Completion'],
                    'nested_structures_tested' => true,
                    'data_permutations_tested' => true,
                ],
                'response_metadata' => [
                    'generated_at' => now()->toISOString(),
                    'request_id' => uniqid('cli_test_', true),
                    'memory_usage' => memory_get_usage(true),
                    'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
                ],
            ];
        });
    }

    /**
     * Run quick test with basic features
     */
    private function runQuickTest()
    {
        $this->info('⚡ Running Quick Logging Test...');

        return $this->logMethod('Quick Logging Test', [
            'test_type' => 'quick_cli_test',
            'timestamp' => now()->toISOString(),
        ], function () {

            $this->logInfo('Quick test info log', ['test' => 'basic']);
            $this->logWarning('Quick test warning log', ['test' => 'warning']);
            $this->logError('Quick test error log', ['test' => 'error']);
            $this->logSuccess('Quick test success log', ['test' => 'success']);

            return ['status' => 'success', 'message' => 'Quick test completed'];
        });
    }

    /**
     * Nested function calls for testing
     */
    private function nestedFunctionCall1()
    {
        return $this->logMethod('First level nested function call', [
            'message' => 'This is a nested function call at level 1',
            'category' => 'Nested Calls',
            'data' => ['call_level' => 1, 'function' => 'nestedFunctionCall1'],
            'input_params' => ['depth' => 1, 'parent_id' => null],
        ], function () {
            return $this->nestedFunctionCall2();
        });
    }

    private function nestedFunctionCall2()
    {
        return $this->logMethod('Second level nested function call', [
            'message' => 'This is a nested function call at level 2',
            'category' => 'Nested Calls',
            'data' => ['call_level' => 2, 'function' => 'nestedFunctionCall2'],
        ], function () {
            return $this->nestedFunctionCall3();
        });
    }

    private function nestedFunctionCall3()
    {
        return $this->logMethod('Third level nested function call', [
            'message' => 'This is a nested function call at level 3',
            'category' => 'Nested Calls',
            'data' => ['call_level' => 3, 'function' => 'nestedFunctionCall3'],
        ], function () {
            return [
                'nested_result' => 'This is the result from the deepest nested function',
                'level' => 3,
                'timestamp' => now()->toISOString(),
                'data' => ['deep_nested' => true, 'complex' => ['a' => 1, 'b' => 2]],
            ];
        });
    }

    /**
     * Test HTTP responses with actual Laravel Response objects
     */
    private function testHttpResponses()
    {
        // Test 200 OK response
        $this->logMethod('Test 200 OK Response', [
            'user_id' => 123,
            'action' => 'get_data',
            'parameters' => ['id' => 1, 'include' => 'details'],
        ], function () {
            return response()->json([
                'message' => 'Success',
                'data' => ['id' => 1, 'name' => 'Test Item'],
            ], 200);
        });

        // Test 400 Bad Request response
        $this->logMethod('Test 400 Bad Request Response', [
            'invalid_data' => 'test',
            'missing_required' => null,
        ], function () {
            return response()->json([
                'error' => 'Bad Request',
                'message' => 'Invalid parameters provided',
            ], 400);
        });

        // Test 404 Not Found response
        $this->logMethod('Test 404 Not Found Response', [
            'requested_id' => 999,
            'resource_type' => 'user',
        ], function () {
            return response()->json([
                'error' => 'Not Found',
                'message' => 'Resource not found',
            ], 404);
        });

        // Test 500 Internal Server Error response
        $this->logMethod('Test 500 Internal Server Error Response', [
            'operation' => 'database_query',
            'query_params' => ['table' => 'users', 'where' => 'id = 1'],
        ], function () {
            return response()->json([
                'error' => 'Internal Server Error',
                'message' => 'Something went wrong',
            ], 500);
        });

        // Test HTML response
        $this->logMethod('Test HTML Response', [
            'template' => 'welcome',
            'variables' => ['title' => 'Hello World'],
        ], function () {
            return response('<h1>Hello World</h1>', 200)
                ->header('Content-Type', 'text/html');
        });

        // Test XML response
        $this->logMethod('Test XML Response', [], function () {
            return response('<xml><message>Hello World</message></xml>', 200)
                ->header('Content-Type', 'application/xml');
        });
    }

    /**
     * Clean up test logs
     */
    private function cleanupTestLogs()
    {
        $this->info('🧹 Cleaning up test logs...');

        $deleted = \Frddl\LaravelSimpleLogging\Models\LogEntry::where('message', 'LIKE', '%test%')
            ->orWhere('message', 'LIKE', '%Test%')
            ->orWhere('message', 'LIKE', '%comprehensive%')
            ->orWhere('message', 'LIKE', '%Comprehensive%')
            ->delete();

        $this->info("Deleted {$deleted} test log entries.");
    }
}
