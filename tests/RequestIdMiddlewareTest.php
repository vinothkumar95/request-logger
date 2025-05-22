<?php

namespace Vinothkumar\RequestLogger\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Vinothkumar\RequestLogger\Http\Middleware\RequestIdMiddleware;

class RequestIdMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Define a simple route for testing the middleware
        Route::middleware(RequestIdMiddleware::class)->get('/_test-route', function (Request $request) {
            return response()->json([
                'message' => 'success',
                'request_id_from_app' => app(config('request-logger.request_attribute_key')),
                'request_id_from_attribute' => $request->attributes->get(config('request-logger.request_attribute_key')),
            ]);
        });
    }

    public function test_id_is_generated_when_not_present()
    {
        $response = $this->get('/_test-route');

        $response->assertStatus(200);

        $headerName = config('request-logger.header_name');
        $this->assertNotEmpty($response->headers->get($headerName));

        $requestId = $response->headers->get($headerName);

        $response->assertJson([
            'request_id_from_app' => $requestId,
            'request_id_from_attribute' => $requestId,
        ]);
    }

    public function test_existing_id_is_used_when_present()
    {
        $existingId = 'my-custom-request-id-123';
        $headerName = config('request-logger.header_name');

        $response = $this->withHeaders([
            $headerName => $existingId,
        ])->get('/_test-route');

        $response->assertStatus(200);
        $this->assertEquals($existingId, $response->headers->get($headerName));

        $response->assertJson([
            'request_id_from_app' => $existingId,
            'request_id_from_attribute' => $existingId,
        ]);
    }
}
