<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Request ID Header Name
    |--------------------------------------------------------------------------
    |
    | This value defines the name of the HTTP header that will be used to
    | store and retrieve the request ID. By default, it is 'X-Request-Id'.
    |
    */
    'header_name' => 'X-Request-Id',

    /*
    |--------------------------------------------------------------------------
    | Request Attribute Key
    |--------------------------------------------------------------------------
    |
    | This value defines the key under which the request ID will be stored
    | as an attribute on the Laravel Request object. This allows easy
    | access to the request ID within your application. By default,
    | it is 'request_id'.
    |
    */
    'request_attribute_key' => 'request_id',
];
