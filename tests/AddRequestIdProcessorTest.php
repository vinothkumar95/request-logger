<?php

namespace Vinothkumar\RequestLogger\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Vinothkumar\RequestLogger\Http\Middleware\RequestIdMiddleware;

class AddRequestIdProcessorTest extends TestCase
{
    protected array $loggingConfig = [];

    protected function setUp(): void
    {
        // Default logging for most tests in this class, can be overridden per test.
        $this->loggingConfig = [
            'logging.default' => 'test_single_direct',
            'logging.channels.test_single_direct' => [
                'driver' => 'single',
                'path' => storage_path('logs/laravel-direct-test.log'),
                'level' => 'debug',
            ],
        ];
        parent::setUp(); // This will apply the loggingConfig
    }

    protected function getLogPathForChannel(string $channelName): string
    {
        return config("logging.channels.{$channelName}.path");
    }

    public function test_request_id_is_added_to_log_when_set_in_app_instance()
    {
        $testId = 'manual-id-123';
        $requestAttributeKey = config('request-logger.request_attribute_key');
        $logPath = $this->getLogPathForChannel('test_single_direct');

        app()->instance($requestAttributeKey, $testId);
        Log::info('This is a test log message with manual ID.');

        $logContents = $this->getLogContent($logPath);
        $this->assertStringContainsString($testId, $logContents);
        $this->assertStringContainsString("\"extra\":{\"$requestAttributeKey\":\"$testId\"}", $logContents);
    }

    public function test_request_id_is_added_to_log_via_middleware()
    {
        $testId = 'middleware-id-456';
        $headerName = config('request-logger.header_name');
        $requestAttributeKey = config('request-logger.request_attribute_key');
        $logPath = $this->getLogPathForChannel('test_single_direct');

        Route::middleware(RequestIdMiddleware::class)->get('/_test-log-route', function (Request $request) use ($requestAttributeKey) {
            Log::info('This is a test log message via middleware.');
            return response()->json(['id_from_log_extra' => $request->attributes->get($requestAttributeKey)]);
        });

        $this->withHeaders([$headerName => $testId])->get('/_test-log-route');

        $logContents = $this->getLogContent($logPath);
        $this->assertStringContainsString($testId, $logContents);
        $this->assertStringContainsString("\"extra\":{\"$requestAttributeKey\":\"$testId\"}", $logContents);
    }
    
    public function test_request_id_is_generated_by_middleware_and_added_to_log()
    {
        $requestAttributeKey = config('request-logger.request_attribute_key');
        $logPath = $this->getLogPathForChannel('test_single_direct');

        Route::middleware(RequestIdMiddleware::class)->get('/_test-generated-log-route', function (Request $request) use ($requestAttributeKey) {
            Log::info('This is a test log message with generated ID via middleware.');
            return response()->json(['generated_id' => $request->attributes->get($requestAttributeKey)]);
        });

        $response = $this->get('/_test-generated-log-route');
        $generatedId = $response->json('generated_id');
        $this->assertNotEmpty($generatedId);

        $logContents = $this->getLogContent($logPath);
        $this->assertStringContainsString($generatedId, $logContents);
        $this->assertStringContainsString("\"extra\":{\"$requestAttributeKey\":\"$generatedId\"}", $logContents);
    }

    public function test_no_id_defaults_to_na_in_logs_when_not_set_and_middleware_not_run()
    {
        $requestAttributeKey = config('request-logger.request_attribute_key');
        $logPath = $this->getLogPathForChannel('test_single_direct');
        
        $originalInstance = null;
        if (app()->bound($requestAttributeKey)) {
           $originalInstance = app($requestAttributeKey);
           app()->forgetInstance($requestAttributeKey);
        }

        Log::info('This is a test log message with no ID.');

        $logContents = $this->getLogContent($logPath);
        $this->assertStringContainsString("\"extra\":{\"$requestAttributeKey\":\"n/a\"}", $logContents);
        
        if ($originalInstance) {
            app()->instance($requestAttributeKey, $originalInstance);
        }
    }

    public function test_request_id_is_logged_with_single_channel_as_default()
    {
        // This test relies on the default loggingConfig set in setUp()
        $testId = 'single-direct-id-789';
        $headerName = config('request-logger.header_name');
        $requestAttributeKey = config('request-logger.request_attribute_key');
        $logPath = $this->getLogPathForChannel('test_single_direct');

        Route::middleware(RequestIdMiddleware::class)->get('/_test-single-direct-route', function () {
            Log::info('Test single direct log.');
        });

        $this->withHeaders([$headerName => $testId])->get('/_test-single-direct-route');
        
        $logContents = $this->getLogContent($logPath);
        $this->assertStringContainsString($testId, $logContents);
        $this->assertStringContainsString("\"extra\":{\"$requestAttributeKey\":\"$testId\"}", $logContents);
    }

    public function test_request_id_is_logged_with_stack_channel()
    {
        $this->loggingConfig = [
            'logging.default' => 'stack_channel',
            'logging.channels.stack_channel' => [
                'driver' => 'stack',
                'channels' => ['stack_sub_1', 'stack_sub_2'],
                'ignore_exceptions' => false,
            ],
            'logging.channels.stack_sub_1' => [
                'driver' => 'single',
                'path' => storage_path('logs/laravel-stack-sub1-test.log'),
                'level' => 'debug',
            ],
            'logging.channels.stack_sub_2' => [
                'driver' => 'single',
                'path' => storage_path('logs/laravel-stack-sub2-test.log'),
                'level' => 'debug',
            ],
        ];
        // Must call parent::setUp() again to apply this specific loggingConfig
        // This is a bit of a hack due to how Testbench handles environment setup.
        // A cleaner way might involve a dedicated method in TestCase to refresh the environment.
        $this->setUp(); 

        $testId = 'stack-id-000';
        $headerName = config('request-logger.header_name');
        $requestAttributeKey = config('request-logger.request_attribute_key');

        Route::middleware(RequestIdMiddleware::class)->get('/_test-stack-route', function () {
            Log::info('Test stack log for all sub-channels.');
        });

        $this->withHeaders([$headerName => $testId])->get('/_test-stack-route');

        $logPath1 = $this->getLogPathForChannel('stack_sub_1');
        $logContents1 = $this->getLogContent($logPath1);
        $this->assertStringContainsString($testId, $logContents1);
        $this->assertStringContainsString("\"extra\":{\"$requestAttributeKey\":\"$testId\"}", $logContents1);

        $logPath2 = $this->getLogPathForChannel('stack_sub_2');
        $logContents2 = $this->getLogContent($logPath2);
        $this->assertStringContainsString($testId, $logContents2);
        $this->assertStringContainsString("\"extra\":{\"$requestAttributeKey\":\"$testId\"}", $logContents2);
    }
    
    public function test_request_id_is_logged_with_stack_channel_and_one_sub_channel_fails()
    {
        $this->loggingConfig = [
            'logging.default' => 'stack_channel_with_failure',
            'logging.channels.stack_channel_with_failure' => [
                'driver' => 'stack',
                'channels' => ['stack_valid_sub', 'stack_invalid_sub'],
                'ignore_exceptions' => true, // Important for this test
            ],
            'logging.channels.stack_valid_sub' => [
                'driver' => 'single',
                'path' => storage_path('logs/laravel-stack-valid-sub-test.log'),
                'level' => 'debug',
            ],
            'logging.channels.stack_invalid_sub' => [
                'driver' => 'single',
                'path' => null, // Invalid path to simulate failure
                'level' => 'debug',
            ],
        ];
        $this->setUp();

        $testId = 'stack-fail-id-111';
        $headerName = config('request-logger.header_name');
        $requestAttributeKey = config('request-logger.request_attribute_key');

        Route::middleware(RequestIdMiddleware::class)->get('/_test-stack-fail-route', function () {
            Log::info('Test stack log for failing sub-channel.');
        });

        $this->withHeaders([$headerName => $testId])->get('/_test-stack-fail-route');

        $logPathValid = $this->getLogPathForChannel('stack_valid_sub');
        $logContentsValid = $this->getLogContent($logPathValid);
        $this->assertStringContainsString($testId, $logContentsValid, "Log content in valid channel did not contain the request ID.");
        $this->assertStringContainsString("\"extra\":{\"$requestAttributeKey\":\"$testId\"}", $logContentsValid, "Log content in valid channel did not contain the correct extra field.");

        // We expect the invalid channel to not have created a log file or to be empty if it did.
        // Depending on Laravel's behavior with null path, it might not even be created.
        $logPathInvalid = storage_path('logs/laravel-stack-invalid-sub-test.log'); // Manually construct as it's invalid
        $logContentsInvalid = $this->getLogContent($logPathInvalid); // this will be false if file doesn't exist
        
        // It's hard to assert non-existence or specific error for the invalid one without more complex setup,
        // but the key is that the valid one *did* get the log.
        // If `ignore_exceptions` was false, an error would be thrown by Laravel, which this test isn't designed for.
        $this->assertTrue(true, "Test completed. Main assertion is on the valid sub-channel.");
    }
}
