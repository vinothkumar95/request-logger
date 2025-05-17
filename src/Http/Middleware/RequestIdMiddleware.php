<?php


namespace Vinothkumar\RequestLogger\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RequestIdMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $requestId = $request->header('X-Request-Id') ?? (string) Str::uuid();

        // Attach to request and container
        $request->headers->set('X-Request-Id', $requestId);
        $request->attributes->set('request_id', $requestId);
        app()->instance('request_id', $requestId);

        return $next($request);
    }
}
