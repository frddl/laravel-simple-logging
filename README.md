# Laravel Simple Logging

A simple, elegant logging package for Laravel with automatic method wrapping and a beautiful web interface.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/frddl/laravel-simple-logging.svg?style=flat-square)](https://packagist.org/packages/frddl/laravel-simple-logging)
[![Total Downloads](https://img.shields.io/packagist/dt/frddl/laravel-simple-logging.svg?style=flat-square)](https://packagist.org/packages/frddl/laravel-simple-logging)
[![License](https://img.shields.io/packagist/l/frddl/laravel-simple-logging.svg?style=flat-square)](https://packagist.org/packages/frddl/laravel-simple-logging)

## Features

- ✅ **Automatic Method Wrapping** - Wrap any method with start/end logging
- ✅ **Beautiful Web Interface** - Modern, responsive log viewer with filtering
- ✅ **Request Tracing** - Group all logs by request ID for easy debugging
- ✅ **Performance Monitoring** - Automatic duration and memory usage tracking
- ✅ **Error Handling** - Automatic exception logging with stack traces
- ✅ **Sensitive Data Sanitization** - Automatically masks sensitive information
- ✅ **Database & File Logging** - Store logs in database and/or files
- ✅ **Zero Dependencies** - Works with any data structure
- ✅ **Mobile Responsive** - Beautiful interface on all devices

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

### 3. Add custom logging within methods:

```php
public function yourMethod(Request $request)
{
    return $this->logMethod('Your Method Name', $request->all(), function() use ($request) {
        // Custom logging
        $this->log('Processing user data', ['user_id' => $request->user_id], 'info');
        
        // Your business logic
        $result = $this->doSomething();
        
        // More custom logging
        $this->log('Operation completed', ['result_count' => count($result)], 'info');
        
        return response()->json(['data' => $result]);
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

### Direct Logging

For custom logging within methods:

```php
// Simple log
$this->log('User logged in', ['user_id' => $user->id]);

// Log with specific level
$this->log('Payment failed', ['error' => $exception->getMessage()], 'error');

// Log with complex data
$this->log('Order processed', [
    'order_id' => $order->id,
    'items' => $order->items->pluck('name'),
    'total' => $order->total
], 'info');
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
