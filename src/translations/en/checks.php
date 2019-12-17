<?php

declare(strict_types=1);

return [
    'redis_can_be_accessed'         => [
        'message' => [
            'not_accessible' => 'The Redis cache can not be accessed: :error',
            'default_cache'  => 'The default cache is not reachable.',
            'named_cache'    => 'The named cache :name is not reachable.',
        ],
        'name'    => 'The Redis cache can be accessed',
    ],
    'servers_are_pingable'          => [
        'message' => "The server ':host' (port: :port) is not reachable (timeout after :timeout seconds).",
        'name'    => 'Required servers are pingable',
    ],
    'php_ini_options'               => [
        'message' => 'The following PHP INI options are different:' . PHP_EOL . ':options',
        'name'    => 'The required PHP INI options are the same',
    ],
    'elasticsearch_can_be_accessed' => [
        'message' => [
            'not_accessible' => 'The Elasticsearch can not be accessed: :error',
        ],
        'name'    => 'The Elasticsearch can be accessed',
    ],
    'available_disk_space'          => [
        'message' => 'The space is less for the paths:' . PHP_EOL . ':options',
        'name'    => 'The available disk spaces are ok',
    ],
    'filesystems_are_available'     => [
        'message' => 'Filesystem disks are not available:' . PHP_EOL . ':options',
        'name'    => 'Filesystem disks are available',
    ],
    'horizon_is_running'          => [
        'message' => 'Horizon process is inactive',
        'name'    => 'Horizon process is running',
    ],
];
