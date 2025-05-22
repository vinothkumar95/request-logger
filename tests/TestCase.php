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

    protected array $logFilesToClear = [];

    protected function getEnvironmentSetUp($app)
    {
        // Set the package's configuration values for consistent testing
        $app['config']->set('request-logger.header_name', 'X-Test-Request-Id');
        $app['config']->set('request-logger.request_attribute_key', 'test_request_id');

        // Allow tests to override logging setup
        if (property_exists($this, 'loggingConfig') && is_array($this->loggingConfig)) {
            foreach ($this->loggingConfig as $key => $value) {
                $app['config']->set($key, $value);
            }
        } else {
            // Default test logging if no specific config provided by the test method
            $app['config']->set('logging.default', 'testlogger');
            $app['config']->set('logging.channels.testlogger', [
                'driver' => 'single',
                'path' => storage_path('logs/laravel-default-test.log'),
                'level' => 'debug',
            ]);
        }

        // Populate logFilesToClear based on the current logging configuration
        $this->populateLogFilesToClear($app['config']);
    }

    protected function populateLogFilesToClear($config): void
    {
        $this->logFilesToClear = [];
        $channels = $config->get('logging.channels', []);
        foreach ($channels as $channelConfig) {
            if (isset($channelConfig['path']) && is_string($channelConfig['path'])) {
                $this->logFilesToClear[$channelConfig['path']] = $channelConfig['path']; // Use path as key to avoid duplicates
            }
            // If it's a stack channel, also add paths from its sub-channels
            if (isset($channelConfig['driver']) && $channelConfig['driver'] === 'stack' && isset($channelConfig['channels']) && is_array($channelConfig['channels'])) {
                foreach($channelConfig['channels'] as $subChannelName) {
                    $subChannelConfig = $config->get("logging.channels.$subChannelName");
                    if (isset($subChannelConfig['path']) && is_string($subChannelConfig['path'])) {
                         $this->logFilesToClear[$subChannelConfig['path']] = $subChannelConfig['path'];
                    }
                }
            }
        }
        if (empty($this->logFilesToClear) && $config->get('logging.channels.testlogger.path')) {
             // Fallback for the old default if nothing else is configured by a test
            $this->logFilesToClear[storage_path('logs/laravel-default-test.log')] = storage_path('logs/laravel-default-test.log');
        }
         if (empty($this->logFilesToClear)) { // Ensure there's at least one default if everything is empty
            $this->logFilesToClear[storage_path('logs/laravel-test.log')] = storage_path('logs/laravel-test.log');
        }
    }


    protected function clearTestLogs(): void
    {
        // Repopulate just in case config changed or setup wasn't fully complete before a previous clear
        if (app()->bound('config')) {
            $this->populateLogFilesToClear(app('config'));
        }

        foreach ($this->logFilesToClear as $logPath) {
            if (file_exists($logPath)) {
                unlink($logPath);
            }
            // Ensure the directory exists for the next log write
            $logDir = dirname($logPath);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0777, true);
            }
        }
    }
    
    /**
     * Helper to get the content of a specific log file.
     *
     * @param string $logPath Full path to the log file.
     * @return string|false
     */
    protected function getLogContent(string $logPath): string|false
    {
        if (file_exists($logPath)) {
            return file_get_contents($logPath);
        }
        return false;
    }


    protected function setUp(): void
    {
        parent::setUp();
        $this->clearTestLogs(); // Clear before each test
    }

    protected function tearDown(): void
    {
        $this->clearTestLogs(); // Clear after each test
        parent::tearDown();
    }
}
