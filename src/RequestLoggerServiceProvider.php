<?php

namespace Vinothkumar\RequestLogger;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Vinothkumar\RequestLogger\Logging\AddRequestIdProcessor;

class RequestLoggerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/request-logger.php', 'request-logger'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/request-logger.php' => config_path('request-logger.php'),
            ], 'request-logger-config');
        }

        $defaultChannelName = config('logging.default');
        $headerName = config('request-logger.header_name', 'X-Request-Id'); // Default added
        $attributeKey = config('request-logger.request_attribute_key', 'request_id'); // Default added

        if ($defaultChannelName === 'stack') {
            $stackChannels = config('logging.channels.stack.channels');
            if (is_array($stackChannels)) {
                foreach ($stackChannels as $channelName) {
                    try {
                        $loggerInstance = Log::channel($channelName);
                        $this->addProcessorToLogger($loggerInstance, $headerName, $attributeKey);
                    } catch (\Throwable $e) {
                        // Optionally log error for this specific channel if needed
                        // error_log("Error adding processor to stack channel $channelName: " . $e->getMessage());
                    }
                }
            }
        } elseif ($defaultChannelName) { // Ensure defaultChannelName is not null
            try {
                $loggerInstance = Log::channel($defaultChannelName);
                $this->addProcessorToLogger($loggerInstance, $headerName, $attributeKey);
            } catch (\Throwable $e) {
                // Optionally log error if needed
                // error_log("Error adding processor to default channel $defaultChannelName: " . $e->getMessage());
            }
        }
    }

    // Helper method to add processor
    protected function addProcessorToLogger($logger, string $headerName, string $attributeKey): void
    {
        $monologInstance = null;
        if ($logger instanceof \Monolog\Logger) {
            $monologInstance = $logger;
        } elseif ($logger instanceof \Illuminate\Log\Logger && method_exists($logger, 'getLogger')) {
            $monologInstance = $logger->getLogger();
        } elseif (method_exists($logger, 'getMonolog')) { 
             $monologInstance = $logger->getMonolog();
        }

        if ($monologInstance instanceof \Monolog\Logger && method_exists($monologInstance, 'pushProcessor')) {
            $monologInstance->pushProcessor(
                new AddRequestIdProcessor($headerName, $attributeKey)
            );
        } elseif (method_exists($logger, 'pushProcessor')) { 
             $logger->pushProcessor(
                 new AddRequestIdProcessor($headerName, $attributeKey)
             );
        } else {
            // error_log("Could not add AddRequestIdProcessor to logger of type: " . get_class($logger));
        }
    }
}
