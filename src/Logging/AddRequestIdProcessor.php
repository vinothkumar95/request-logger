<?php

namespace Vinothkumar\RequestLogger\Logging;

use Monolog\LogRecord;

class AddRequestIdProcessor
{
    public function __invoke(LogRecord $record)
    {
        try {
            if (app()->bound('request') && app('request')->hasHeader('X-Request-Id')) {
                $record['extra']['request_id'] = app('request')->header('X-Request-Id');
            } elseif (app()->bound('request_id')) {
                $record['extra']['request_id'] = app('request_id');
            } else {
                $record['extra']['request_id'] = 'n/a';
            }
        } catch (\Throwable $e) {
            $record['extra']['request_id'] = 'unavailable';
        }

        return $record;
    }
}
