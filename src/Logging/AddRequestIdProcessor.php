<?php

namespace Vinothkumar\RequestLogger\Logging;

use Monolog\LogRecord;

class AddRequestIdProcessor
{
    private string $headerName;
    private string $requestAttributeKey;

    public function __construct(string $headerName, string $requestAttributeKey)
    {
        $this->headerName = $headerName;
        $this->requestAttributeKey = $requestAttributeKey;
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        try {
            $requestId = null;
            if (app()->bound('request')) {
                $request = app('request');
                // Try to get from request attribute first (set by middleware)
                if ($request->attributes->has($this->requestAttributeKey)) {
                    $requestId = $request->attributes->get($this->requestAttributeKey);
                }
                // Fallback to header if not found in attribute (e.g., if middleware hasn't run yet for some reason)
                elseif ($request->hasHeader($this->headerName)) {
                    $requestId = $request->header($this->headerName);
                }
            }
            
            // Fallback if request is not available or ID is not found yet
            if (is_null($requestId) && app()->bound($this->requestAttributeKey)) {
                 $requestId = app($this->requestAttributeKey);
            }

            $record['extra'][$this->requestAttributeKey] = $requestId ?: 'n/a';

        } catch (\Throwable $e) {
            $record['extra'][$this->requestAttributeKey] = 'unavailable';
        }

        return $record;
    }
}
