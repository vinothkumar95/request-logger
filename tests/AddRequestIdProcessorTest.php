<?php

namespace Vinothkumar\RequestLogger\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Vinothkumar\RequestLogger\Http\Middleware\RequestIdMiddleware;

class AddRequestIdProcessorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_request_id_is_added_to_log_when_set_in_app_instance()
    {
        $testId = 'manual-id-123';
        $requestAttributeKey = config('request-logger.request_attribute_key');

        // Manually set the request ID in the app container
        app()->instance($requestAttributeKey, $testId);

        Log::info('This is a test log message with manual ID.');

        $logContents = file_get_contents($this->getTestLogPath());
        $this->assertStringContainsString($testId, $logContents);
        $this->assertStringContainsString("\"extra\":{\"$requestAttributeKey\":\"$testId\"}", $logContents);
    }

    public function test_request_id_is_added_to_log_via_middleware()
    {
        $testId = 'middleware-id-456';
        $headerName = config('request-logger.header_name');
        $requestAttributeKey = config('request-logger.request_attribute_key');

        // Define a route that uses the middleware
        Route::middleware(RequestIdMiddleware::class)->get('/_test-log-route', function (Request $request) use ($requestAttributeKey) {
            Log::info('This is a test log message via middleware.');
            return response()->json([
                'id_from_log_extra' => $request->attributes->get($requestAttributeKey)
            ]);
        });

        $this->withHeaders([$headerName => $testId])->get('/_test-log-route');

        $logContents = file_get_contents($this->getTestLogPath());
        $this->assertStringContainsString($testId, $logContents);
        $this->assertStringContainsString("\"extra\":{\"$requestAttributeKey\":\"$testId\"}", $logContents);
    }
    
    public function test_request_id_is_generated_by_middleware_and_added_to_log()
    {
        $requestAttributeKey = config('request-logger.request_attribute_key');

        // Define a route that uses the middleware
        Route::middleware(RequestIdMiddleware::class)->get('/_test-generated-log-route', function (Request $request) use ($requestAttributeKey) {
            Log::info('This is a test log message with generated ID via middleware.');
            // We need to return the generated ID to assert against it, as it's dynamic
            return response()->json([
                'generated_id' => $request->attributes->get($requestAttributeKey)
            ]);
        });

        $response = $this->get('/_test-generated-log-route');
        $generatedId = $response->json('generated_id');
        $this->assertNotEmpty($generatedId);

        $logContents = file_get_contents($this->getTestLogPath());
        $this->assertStringContainsString($generatedId, $logContents);
        $this->assertStringContainsString("\"extra\":{\"$requestAttributeKey\":\"$generatedId\"}", $logContents);
    }

    public function test_no_id_defaults_to_na_in_logs_when_not_set_and_middleware_not_run()
    {
        // Ensure no ID is set from previous tests or app state for this specific test
        // This is a bit tricky in a shared app container across tests.
        // We rely on the processor's logic to default to 'n/a' if nothing is found.
        // For a truly isolated test here, one might need to unbind/rebind services or use a fresh app instance.
        // However, the current setup should work if the processor correctly defaults.
        
        // We also need to make sure the logger is re-instantiated or processors are cleared
        // if they hold onto state from previous requests in a long-running test process.
        // Testbench usually handles this well per test method.
        
        // Reset any potential global instance that might have been set by other tests or middleware
        $requestAttributeKey = config('request-logger.request_attribute_key');
        if (app()->bound($requestAttributeKey)) {
           // Temporarily unbind to simulate a scenario where it's not set
           $originalInstance = app($requestAttributeKey);
           app()->forgetInstance($requestAttributeKey);
        }

        Log::info('This is a test log message with no ID.');

        $logContents = file_get_contents($this->getTestLogPath());
        $this->assertStringContainsString("\"extra\":{\"$requestAttributeKey\":\"n/a\"}", $logContents);
        
        // Restore if we unbound it
        if (isset($originalInstance)) {
            app()->instance($requestAttributeKey, $originalInstance);
        }
    }
}
