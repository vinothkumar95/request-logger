<?php

namespace Vinothkumar\RequestLogger\Tests;

use Illuminate\Support\Facades\Log;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Vinothkumar\RequestLogger\RequestLoggerServiceProvider;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            RequestLoggerServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Configure a basic logging setup for testing
        $app['config']->set('logging.channels.testlogger', [
            'driver' => 'single',
            'path' => storage_path('logs/laravel-test.log'),
            'level' => 'debug',
        ]);
        $app['config']->set('logging.default', 'testlogger');

        // Set the package's configuration values for consistent testing
        $app['config']->set('request-logger.header_name', 'X-Test-Request-Id');
        $app['config']->set('request-logger.request_attribute_key', 'test_request_id');
    }

    /**
     * Helper to get the test log file path.
     *
     * @return string
     */
    protected function getTestLogPath(): string
    {
        return storage_path('logs/laravel-test.log');
    }

    /**
     * Helper to clear the test log file.
     */
    protected function clearTestLog(): void
    {
        if (file_exists($this->getTestLogPath())) {
            unlink($this->getTestLogPath());
        }
        // Ensure the directory exists for the next log write
        if (!is_dir(dirname($this->getTestLogPath()))) {
            mkdir(dirname($this->getTestLogPath()), 0777, true);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->clearTestLog();
    }

    protected function tearDown(): void
    {
        $this->clearTestLog();
        parent::tearDown();
    }
}
