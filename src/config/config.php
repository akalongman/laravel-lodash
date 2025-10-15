<?php

declare(strict_types=1);

return [
    'locales' => [
        'en' => [
            'name'             => 'English',
            'native_name'      => 'English',
            'flag'             => 'gb',
            'locale'           => 'en',
            'canonical_locale' => 'en_GB',
            'full_locale'      => 'en_GB.UTF-8',
        ],
        'ka' => [
            'name'             => 'Georgian',
            'native_name'      => 'ქართული',
            'flag'             => 'ge',
            'locale'           => 'ka',
            'canonical_locale' => 'ka_GE',
            'full_locale'      => 'ka_GE.UTF-8',
        ],
    ],

    'debug' => [
        'ips' => explode(',', env('DEBUG_IP_LIST', '')), // IP list for enabling debug mode
    ],

    'xss' => [
        'exclude_uris'           => [], // Excluded URI's for Xss middleware
        'x_frame_options'        => 'DENY', // X-Frame-Options header value
        'x_content_type_options' => 'nosniff', // X-Content-Type-Options header value
        'x_xss_protection'       => '1; mode=block', // X-XSS-Protection header value
    ],

    'register' => [
        'request_macros'   => false,
        'translations'     => true,
        'validation_rules' => true,
        'commands'         => true,
    ],
];
