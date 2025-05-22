<?php


namespace Vinothkumar\RequestLogger\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RequestIdMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $headerName = config('request-logger.header_name', 'X-Request-Id');
        $requestAttributeKey = config('request-logger.request_attribute_key', 'request_id');

        $requestId = $request->header($headerName) ?? (string) Str::uuid();

        // Set the request ID on the request object, so it can be easily accessed
        $request->attributes->set($requestAttributeKey, $requestId);

        // Also ensure the header is set on the incoming request if it wasn't there,
        // so subsequent parts of the current request handling (not just response) can see it via header()
        if (!$request->headers->has($headerName)) {
            $request->headers->set($headerName, $requestId);
        }
        
        // Bind the request ID to the service container using the configured key
        app()->instance($requestAttributeKey, $requestId);

        $response = $next($request);

        // Add/update the request ID to the response headers
        $response->headers->set($headerName, $requestId);

        return $response;
    }
}
