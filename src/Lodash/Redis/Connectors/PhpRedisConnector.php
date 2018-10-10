<?php
/*
 * This file is part of the Laravel Lodash package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Longman\LaravelLodash\Redis\Connectors;

use Illuminate\Redis\Connectors\PhpRedisConnector as BasePhpRedisConnector;
use Redis;

class PhpRedisConnector extends BasePhpRedisConnector
{
    /**
     * Create the Redis client instance.
     *
     * @param  array $config
     * @return \Redis
     */
    protected function createClient(array $config)
    {
        return tap(new Redis, function (Redis $client) use ($config) {
            $this->establishConnection($client, $config);

            if (! empty($config['password'])) {
                $client->auth((string) $config['password']);
            }

            if (! empty($config['database'])) {
                $client->select((int) $config['database']);
            }

            if (! empty($config['prefix'])) {
                $client->setOption(Redis::OPT_PREFIX, (string) $config['prefix']);
            }

            if (! empty($config['read_timeout'])) {
                $client->setOption(Redis::OPT_READ_TIMEOUT, (string) $config['read_timeout']);
            }

            if (! empty($config['serializer'])) {
                $serializer = 0;
                switch ($config['serializer']) {
                    case 'igbinary':
                        $serializer = Redis::SERIALIZER_IGBINARY;
                        break;
                    case 'php':
                        $serializer = Redis::SERIALIZER_PHP;
                        break;
                }
                $client->setOption(Redis::OPT_SERIALIZER, (string) $serializer);
            }

            if (! empty($config['scan'])) {
                $client->setOption(Redis::OPT_SCAN, Redis::SCAN_RETRY);
            }
        });
    }
}
