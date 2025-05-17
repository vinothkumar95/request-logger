<?php

namespace Vinothkumar\RequestLogger;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Vinothkumar\RequestLogger\Logging\AddRequestIdProcessor;

class RequestLoggerServiceProvider extends ServiceProvider
{
    protected function addRequestIdProcessor(): void
    {
        try {
            $logger = Log::getLogger();

            // Prevent duplicate processors
            static $pushed = false;
            if ($pushed) return;

            if (method_exists($logger, 'pushProcessor')) {
                $logger->pushProcessor(new AddRequestIdProcessor());
                $pushed = true;
            }
        } catch (\Throwable $e) {
            error_log('RequestId Processor Error: ' . $e->getMessage());
        }
    }

    public function register(): void
    {
        $this->app->booting(function () {
            $this->addRequestIdProcessor();
        });
    }
}
