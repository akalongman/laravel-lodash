<?php

return [

    'debug' => [
        'ips' => [
            // IP list for enabling debug mode
        ],
    ],

    'cors' => [
        'allow_methods' => [
            'HEAD',
            'GET',
            'POST',
            'OPTIONS',
            'PUT',
            'PATCH',
            'DELETE',
        ],
        'allow_origins' => explode(',', env('CORS_ALLOW_ORIGINS', '')),
        'allow_headers' => [
            'Origin',
            'X-Requested-With',
            'Content-Type',
            'Accept',
            'Authorization',
        ],
    ],

];
