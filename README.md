# Laravel Request Logger

`vinothkumar/request-logger` is a Laravel package designed to simplify request tracing and debugging by automatically injecting a unique Request ID into every log entry generated during a request's lifecycle. This allows for easier tracking of a request's journey through various parts of your application, including controllers, services, jobs, and more.

## 🚀 Features

*   **Automatic Request ID Injection:** Seamlessly adds a unique ID to each incoming request.
*   **Integrated Logging:** Automatically appends the `request_id` to all log entries made using Laravel's default logging system.
*   **Configurable Header:** Allows customization of the HTTP header name used for the request ID (default: `X-Request-Id`).
*   **Configurable Attribute Key:** Enables setting a custom key for accessing the request ID on the Request object and via the application container (default: `request_id`).
*   **Laravel Compatibility:** Supports Laravel versions 10 and 11.
*   **Auto-Discovery:** Service provider is auto-discovered by Laravel.
*   **Middleware Based:** Uses middleware to manage the request ID lifecycle.

## 📦 Installation

Install the package into your Laravel project using Composer:

```bash
composer require vinothkumar/request-logger
```

## ⚙️ Configuration

The package's service provider is automatically registered thanks to Laravel's auto-discovery feature.

To customize the default settings, you can publish the configuration file:

```bash
php artisan vendor:publish --provider="Vinothkumar\RequestLogger\RequestLoggerServiceProvider" --tag="request-logger-config"
```

This will create a `config/request-logger.php` file in your application. The available options are:

*   `header_name` (string):
    *   Defines the name of the HTTP header used to carry the request ID. If an incoming request includes this header, its value will be used as the request ID; otherwise, a new ID will be generated. This header is also added to outgoing responses.
    *   Default: `'X-Request-Id'`

*   `request_attribute_key` (string):
    *   Defines the key under which the request ID is stored as an attribute on the Laravel `Request` object (`$request->attributes`). It's also used as the binding key for the request ID instance in the service container (`app(...)`).
    *   Default: `'request_id'`

## 🔗 Middleware Registration

The `RequestIdMiddleware` is responsible for identifying or generating the request ID and making it available to your application. You need to register this middleware manually.

For global usage (recommended for most applications), add it to the `$middleware` property in your `app/Http/Kernel.php` file:

```php
// app/Http/Kernel.php

protected $middleware = [
    // ... other global middleware
    \Vinothkumar\RequestLogger\Http\Middleware\RequestIdMiddleware::class,
    // ... other global middleware
];
```

Alternatively, you can register it for specific routes or route groups if you only need request ID logging for certain parts of your application.

## 🛠️ How it Works / Usage

Once the package is installed, configured, and the middleware is registered, it works automatically.

1.  The `RequestIdMiddleware` inspects incoming requests:
    *   If the configured header (e.g., `X-Request-Id`) is present, its value is used as the request ID.
    *   If the header is not present, a new UUID v4 is generated to serve as the request ID.
2.  The request ID is then:
    *   Set as an attribute on the current `Request` object, accessible via `$request->attributes->get('your_configured_key')`.
    *   Bound to the service container, accessible via `app('your_configured_key')`.
    *   Added to the response headers for outgoing responses.
3.  The `AddRequestIdProcessor` (automatically registered with your default logger) then appends this request ID to every log entry made through Laravel's `Log` facade or logger instance.

**Example Log Entry:**

When you use `Log::info('This is a log message.');` or similar, the output in your log file will look something like this (assuming default configuration):

```
[2023-10-27 10:00:00] local.INFO: This is a log message. {"extra":{"request_id":"your-unique-request-id"}} 
```

You can also access the request ID directly within your application code after the middleware has run:

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

// In a controller method
public function someAction(Request $request)
{
    $requestIdFromAttribute = $request->attributes->get(config('request-logger.request_attribute_key'));
    $requestIdFromApp = app(config('request-logger.request_attribute_key'));

    Log::info('Current request ID from attribute: ' . $requestIdFromAttribute);
    // ... your logic
}
```

## 🧪 Testing

The package includes a suite of unit tests to ensure its functionality. If you've cloned the repository locally, you can run the tests using:

```bash
composer test
```

(This assumes you have a "test" script defined in your `composer.json`, like `"test": "vendor/bin/phpunit"` or similar.)

## 📄 License

This package is open-source software licensed under the The MIT License (MIT). Please see the [LICENSE](LICENSE) file for more information.
(Note: A `LICENSE` file should be present in the repository root.)
