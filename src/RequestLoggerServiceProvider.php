<?php

namespace Vinothkumar\RequestLogger;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Vinothkumar\RequestLogger\Logging\AddRequestIdProcessor;

class RequestLoggerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/request-logger.php' => config_path('request-logger.php'),
            ], 'request-logger-config');
        }

        $this->addRequestIdProcessor();
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/request-logger.php', 'request-logger'
        );
    }

    protected function addRequestIdProcessor(): void
    {
        try {
            $defaultChannel = config('logging.default');
            if ($defaultChannel) {
                $logger = Log::channel($defaultChannel);

                if (method_exists($logger, 'pushProcessor')) {
                    $logger->pushProcessor(new AddRequestIdProcessor(
                        config('request-logger.header_name', 'X-Request-Id'),
                        config('request-logger.request_attribute_key', 'request_id')
                    ));
                }
            }
        } catch (\Throwable $e) {
            // Let Laravel's standard error handling manage it.
        }
    }
}
