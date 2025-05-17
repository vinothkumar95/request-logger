# Laravel Request Logger

**`vinothkumar/request-logger`** is a Laravel package that automatically logs a unique Request ID with every log entry during a request's lifecycle. This helps trace the flow and debug complex request journeys across controllers, services, jobs, and more.

---

## 🚀 Features

- Injects a unique `request_id` into every incoming request
- Automatically appends `request_id` to all logs (info, debug, error, etc.)
- Compatible with Laravel 9, 10, and 11
- Works with multi-channel log setups (e.g., `stack`, `single`, `daily`)
- Easy integration via middleware and processor

---

## 📦 Installation

1. **Add to your Laravel project via local path or private repo**

If using local development:

```json
// In your Laravel app's composer.json
"repositories": [
  {
    "type": "path",
    "url": "packages/vinothkumar/request-logger"
  }
]
````

Then install:

````
composer require vinothkumar/request-logger:@dev
````

Register Middleware in bootstrap\app.php

````

use Vinothkumar\RequestLogger\Http\Middleware\RequestIdMiddleware;

$middleware->append(RequestIdMiddleware::class)


````

🧪 Testing

To test if it's working:

````
Log::info("This is a test log");

````

📄 Example Output

````
[2025-05-17 06:45:33] local.info: This is a test log  {"request_id":"9d9b88ce-e76b-444c-a722-890d5ef418ec"}

````
