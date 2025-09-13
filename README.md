# Laravel Simple Logging

An elegant solution for monitoring your Laravel application's actual flow with comprehensive logging of warnings, infos, debug variables, and performance metrics. Features automatic method wrapping and a beautiful web interface for real-time application monitoring.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/frddl/laravel-simple-logging.svg?style=flat-square)](https://packagist.org/packages/frddl/laravel-simple-logging)
[![Total Downloads](https://img.shields.io/packagist/dt/frddl/laravel-simple-logging.svg?style=flat-square)](https://packagist.org/packages/frddl/laravel-simple-logging)
[![License](https://img.shields.io/packagist/l/frddl/laravel-simple-logging.svg?style=flat-square)](https://packagist.org/packages/frddl/laravel-simple-logging)
[![Tests](https://github.com/frddl/laravel-simple-logging/workflows/Tests/badge.svg)](https://github.com/frddl/laravel-simple-logging/actions)
[![Code Style](https://github.com/frddl/laravel-simple-logging/workflows/Code%20Style/badge.svg)](https://github.com/frddl/laravel-simple-logging/actions)
[![PHP Version](https://img.shields.io/packagist/php-v/frddl/laravel-simple-logging.svg?style=flat-square)](https://packagist.org/packages/frddl/laravel-simple-logging)
[![Laravel Version](https://img.shields.io/badge/Laravel-8%2B-red.svg?style=flat-square)](https://laravel.com)

<img width="1600" height="595" alt="image" src="https://github.com/user-attachments/assets/de8a4798-77f5-4b2b-8003-f51211d7bbb3" />

## Features

- 🎯 **Real-Time Application Flow Monitoring** - Track your app's actual execution flow with comprehensive logging
- ⚠️ **Smart Warning & Info Logging** - Automatically capture warnings, infos, and debug variables throughout your application
- 🔍 **Advanced Debug Variable Tracking** - Monitor complex data structures, arrays, objects, and variables in real-time
- ✅ **Automatic Method Wrapping** - Wrap any method with start/end logging for complete flow visibility
- 🎨 **Beautiful Web Interface** - Modern, responsive log viewer with advanced filtering and search
- 🔗 **Request Tracing** - Group all logs by request ID for easy debugging and flow analysis
- 📊 **Performance Monitoring** - Automatic duration and memory usage tracking for optimization
- 🚨 **Intelligent Error Handling** - Automatic exception logging with detailed stack traces
- 🔒 **Sensitive Data Sanitization** - Automatically masks passwords, tokens, and sensitive information
- 💾 **Flexible Storage** - Store logs in database and/or files with configurable retention
- 🚀 **Zero Dependencies** - Works with any data structure and Laravel version
- 📱 **Mobile Responsive** - Beautiful interface on all devices for monitoring on-the-go

## 🎯 Perfect for Application Flow Monitoring

This package is specifically designed for **monitoring your Laravel application's actual execution flow**. It excels at:

- **🔍 Debug Variable Tracking** - Monitor complex data structures, arrays, objects, and variables as they flow through your application
- **⚠️ Warning & Info Capture** - Automatically log warnings, infos, and debug messages with full context
- **📊 Real-Time Flow Analysis** - See exactly how your application processes requests, with timing and memory usage
- **🚨 Exception Tracking** - Capture and analyze errors with complete stack traces and variable states
- **🔗 Request Tracing** - Follow a single request through your entire application stack
- **📈 Performance Insights** - Identify bottlenecks and optimize your application based on real usage data

Perfect for debugging complex business logic, monitoring API responses, tracking user interactions, and understanding your application's behavior in production.

## Installation

```bash
composer require frddl/laravel-simple-logging
```

Publish the config file and migrations:

```bash
php artisan vendor:publish --provider="Frddl\LaravelSimpleLogging\SimpleLoggingServiceProvider" --tag="config"
php artisan vendor:publish --provider="Frddl\LaravelSimpleLogging\SimpleLoggingServiceProvider" --tag="migrations"
```

Run the migrations:

```bash
php artisan migrate
```

## Quick Start

### 1. Add the trait to your controller:

```php
use Frddl\LaravelSimpleLogging\Traits\SimpleLoggingTrait;

class YourController extends Controller
{
    use SimpleLoggingTrait;
    
    // Your methods here
}
```

### 2. Wrap your methods with automatic logging:

```php
public function yourMethod(Request $request)
{
    return $this->logMethod('Your Method Name', $request->all(), function() use ($request) {
        // Your business logic here - automatic start/end logging!
        return response()->json(['message' => 'Success']);
    });
}
```

### 3. Add comprehensive monitoring with debug variables:

```php
public function processOrder(Request $request)
{
    return $this->logMethod('Process Order', $request->all(), function() use ($request) {
        // Monitor input data and validation
        $this->log('Order validation started', [
            'order_data' => $request->all(),
            'user_id' => $request->user_id,
            'items_count' => count($request->items ?? [])
        ], 'info');
        
        // Track business logic execution
        $order = Order::create($request->validated());
        $this->log('Order created successfully', [
            'order_id' => $order->id,
            'total_amount' => $order->total,
            'payment_method' => $order->payment_method
        ], 'info');
        
        // Monitor external API calls
        $payment = $this->processPayment($order);
        $this->log('Payment processed', [
            'payment_id' => $payment->id,
            'status' => $payment->status,
            'amount' => $payment->amount
        ], 'info');
        
        // Track warnings and potential issues
        if ($payment->amount !== $order->total) {
            $this->log('Payment amount mismatch detected', [
                'order_total' => $order->total,
                'payment_amount' => $payment->amount,
                'difference' => $payment->amount - $order->total
            ], 'warning');
        }
        
        return response()->json(['order' => $order]);
    });
}
```

## Configuration

Edit `config/simple-logging.php`:

```php
return [
    'enabled' => env('SIMPLE_LOGGING_ENABLED', true),
    'file_logging' => env('SIMPLE_LOGGING_FILE_LOGGING', false),
    'database_logging' => env('SIMPLE_LOGGING_DATABASE_LOGGING', true),
    'log_level' => env('SIMPLE_LOGGING_LEVEL', 'info'),
    'route_prefix' => 'logs',
    'middleware' => ['web'],
    'viewer' => [
        'per_page' => 50,
    ],
    'export' => [
        'max_records' => 100,
    ],
    'cleanup_old_logs_days' => env('SIMPLE_LOGGING_CLEANUP_DAYS', 30),
];
```

## Log Cleanup & Maintenance

The package includes automatic log cleanup to prevent database bloat:

### Automatic Cleanup
- **Scheduled Daily** - Runs automatically at 2:00 AM daily
- **Configurable Retention** - Keep logs for specified days (default: 30 days)
- **Background Processing** - Runs without blocking your application

### Manual Cleanup
```bash
# Clean up logs older than 30 days (uses config default)
php artisan simple-logging:cleanup

# Clean up logs older than 7 days
php artisan simple-logging:cleanup --days=7

# Clean up logs older than 90 days
php artisan simple-logging:cleanup --days=90
```

### Configuration
```php
// In config/simple-logging.php
'cleanup_old_logs_days' => env('SIMPLE_LOGGING_CLEANUP_DAYS', 30),

// In your .env file
SIMPLE_LOGGING_CLEANUP_DAYS=30
```

### Disable Cleanup
Set the cleanup days to `null` or `0` to disable automatic cleanup:
```php
'cleanup_old_logs_days' => null, // Disable cleanup
```

## Usage

### Automatic Method Logging

The `logMethod` function automatically logs:
- Method start with input data
- Method completion with duration and memory usage
- Any exceptions with stack traces

```php
public function processOrder(Request $request)
{
        return $this->logMethod('Process Order', $request->all(), function() use ($request) {
        $order = Order::create($request->validated());
        
        $this->log('Order created', ['order_id' => $order->id], 'info');
        
        // Process payment
        $payment = $this->processPayment($order);
        
        $this->log('Payment processed', ['payment_id' => $payment->id], 'info');
        
        return response()->json(['order' => $order]);
    });
}
```

### Direct Logging for Application Flow Monitoring

Monitor your application's execution flow with detailed logging:

```php
// Monitor user actions and debug variables
$this->log('User authentication started', [
    'email' => $request->email,
    'ip_address' => $request->ip(),
    'user_agent' => $request->userAgent(),
    'session_id' => session()->getId()
], 'info');

// Track business logic with complex data structures
$this->log('Order processing initiated', [
    'order_id' => $order->id,
    'customer_data' => $order->customer->toArray(),
    'items' => $order->items->map(function($item) {
        return [
            'name' => $item->name,
            'price' => $item->price,
            'quantity' => $item->quantity
        ];
    }),
    'total_calculation' => [
        'subtotal' => $order->subtotal,
        'tax' => $order->tax,
        'shipping' => $order->shipping,
        'total' => $order->total
    ]
], 'info');

// Monitor warnings and potential issues
$this->log('Low inventory warning', [
    'product_id' => $product->id,
    'current_stock' => $product->stock,
    'requested_quantity' => $quantity,
    'threshold' => $product->low_stock_threshold
], 'warning');

// Track errors with full context
$this->log('Payment processing failed', [
    'error' => $exception->getMessage(),
    'order_id' => $order->id,
    'payment_data' => $paymentData,
    'retry_count' => $retryCount,
    'stack_trace' => $exception->getTraceAsString()
], 'error');
```

### Log Levels

- `emergency` - System is unusable
- `alert` - Action must be taken immediately  
- `critical` - Critical conditions
- `error` - Error conditions
- `warning` - Warning conditions
- `notice` - Normal but significant conditions
- `info` - Informational messages (default)
- `debug` - Debug-level messages

## Web Interface

Access the log viewer at: `http://your-app.com/logs`

### Features:
- **Request Tracing** - See all logs grouped by request
- **Advanced Filtering** - Filter by level, date, search terms
- **Real-time Viewing** - Beautiful, responsive interface
- **Performance Metrics** - Duration and memory usage tracking
- **Mobile Friendly** - Works perfectly on all devices

## Disable Logging

### Environment Variable (Recommended):
```bash
SIMPLE_LOGGING_ENABLED=false
```

### Or in code:
```php
config(['simple-logging.enabled' => false]);
```

## API Endpoints

- `GET /logs` - Web interface
- `GET /logs/api` - JSON API for logs
- `GET /logs/api/statistics` - Log statistics
- `GET /logs/api/property-keys` - Available property keys

## Sensitive Data Sanitization

The package automatically sanitizes sensitive data:

```php
// This will be automatically masked
$this->log('User data', [
    'password' => 'secret123',           // → [HIDDEN]
    'token' => 'abc123xyz',              // → [HIDDEN]
    'authorization' => 'Bearer token123' // → Bearer [HIDDEN]
]);
```

## Performance

- **Minimal Overhead** - Only logs when enabled
- **Efficient Storage** - Optimized database queries
- **Memory Conscious** - Tracks memory usage
- **Configurable Cleanup** - Automatic old log removal

## Examples

### Complete Controller Example

```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use Frddl\LaravelSimpleLogging\Traits\SimpleLoggingTrait;

class OrderController extends Controller
{
    use SimpleLoggingTrait;

    public function store(CreateOrderRequest $request)
    {
        return $this->logMethod('Create Order', $request->all(), function() use ($request) {
            $this->log('Validating order data', ['items_count' => count($request->items)]);
            
            $order = Order::create($request->validated());
            
            $this->log('Order created successfully', ['order_id' => $order->id]);
            
            // Process payment
            $payment = $this->processPayment($order);
            
            if (!$payment->success) {
                $this->log('Payment failed', ['error' => $payment->error], 'error');
                throw new PaymentException('Payment processing failed');
            }
            
            $this->log('Payment successful', ['payment_id' => $payment->id]);
            
            return response()->json(['order' => $order], 201);
        });
    }
    
    private function processPayment($order)
    {
        return $this->logMethod('Process Payment', ['order_id' => $order->id], function() use ($order) {
            // Payment logic here
            return (object) ['success' => true, 'id' => 'pay_123'];
        });
    }
}
```

## Testing

```bash
composer test
```

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Farid](https://github.com/frddl)
- [All Contributors](../../contributors)

## Support

If you discover any issues, please [open an issue](https://github.com/frddl/laravel-simple-logging/issues) on GitHub.
