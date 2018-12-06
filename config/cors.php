<?php

return [
    'allowed_origins' => env('CORS_ALLOWED_ORIGINS', '*'),
    'allowed_headers' => env('CORS_ALLOWED_HEADERS', join(',',
        [
            'Origin',
            'X-Requested-With',
            'Content-Type',
            'Accept',
            'Authorization',
            'Stream-Auth-Type'
        ]
    )),
    'allowed_methods' => env('CORS_ALLOWED_METHODS', join(',',
        [
            'GET',
            'POST',
            'PUT',
            'DELETE',
            'OPTIONS',
            'PATCH',
            'HEAD',
        ]
    )),
];
