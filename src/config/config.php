<?php
declare(strict_types=1);

use Longman\LaravelLodash\Commands;

return [
    'debug' => [
        'ips' => [ // IP list for enabling debug mode
            //'127.0.0.1',
        ],
    ],

    'cors' => [
        'allow_methods' => [ // Allowed request methods
            'HEAD',
            'GET',
            'POST',
            'OPTIONS',
            'PUT',
            'PATCH',
            'DELETE',
        ],
        'allow_origins' => [ // Allowed domains
            //'locahost',
        ],
        'allow_headers' => [ // Allowed headers
            'Origin',
            'X-Requested-With',
            'Content-Type',
            'Accept',
            'Authorization',
        ],
    ],

    'xss' => [
        'exclude_uris'           => [], // Excluded URI's for Xss middleware
        'x_frame_options'        => 'DENY', // X-Frame-Options header value
        'x_content_type_options' => 'nosniff', // X-Content-Type-Options header value
        'x_xss_protection'       => '1; mode=block', // X-XSS-Protection header value
    ],

    'register_blade_directives' => [
        'datetime' => true,
        'plural'   => true,
    ],

    'register_request_macros' => [
        'getInt'    => true,
        'getBool'   => true,
        'getFloat'  => true,
        'getString' => true,
    ],

    'available_commands' => [
        'command.lodash.clear-all'     => Commands\ClearAll::class,
        'command.lodash.db.clear'      => Commands\DbClear::class,
        'command.lodash.db.dump'       => Commands\DbDump::class,
        'command.lodash.db.restore'    => Commands\DbRestore::class,
        'command.lodash.log.clear'     => Commands\LogClear::class,
        'command.lodash.user.add'      => Commands\UserAdd::class,
        'command.lodash.user.password' => Commands\UserPassword::class,
    ],
];
