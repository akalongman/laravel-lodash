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

use Illuminate\Redis\Connections\PhpRedisClusterConnection;
use Illuminate\Redis\Connectors\PhpRedisConnector as BasePhpRedisConnector;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Redis;
use RedisArray;

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
        return tap(new Redis(), function (Redis $client) use ($config) {
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
                $serializer = $this->getSerializerFromConfig($config['serializer']);
                $client->setOption(Redis::OPT_SERIALIZER, (string) $serializer);
            }

            if (! empty($config['scan'])) {
                $client->setOption(Redis::OPT_SCAN, Redis::SCAN_RETRY);
            }
        });
    }

    /**
     * Create a new clustered PhpRedis connection.
     *
     * @param  array $config
     * @param  array $clusterOptions
     * @param  array $options
     * @return \Illuminate\Redis\Connections\PhpRedisClusterConnection
     */
    public function connectToCluster(array $config, array $clusterOptions, array $options)
    {
        $options = array_merge($options, $clusterOptions, Arr::pull($config, 'options', []));

        // Use native Redis clustering
        if (Arr::get($options, 'cluster') === 'redis') {
            return new PhpRedisClusterConnection($this->createRedisClusterInstance(
                array_map([$this, 'buildClusterConnectionString'], $config),
                $options
            ));
        }

        // Use client-side sharding
        return new PhpRedisClusterConnection($this->createRedisArrayInstance(
            array_map([$this, 'buildRedisArrayConnectionString'], $config),
            $options
        ));
    }

    /**
     * Build a PhpRedis hosts array.
     *
     * @param  array $server
     * @return string
     */
    protected function buildRedisArrayConnectionString(array $server)
    {
        return $server['host'] . ':' . $server['port'];
    }

    /**
     * Create a new redis array instance.
     *
     * @param  array $servers
     * @param  array $options
     * @return \RedisArray
     */
    protected function createRedisArrayInstance(array $servers, array $options)
    {
        $client = new RedisArray($servers, Arr::only($options, [
            'function',
            'previous',
            'retry_interval',
            'lazy_connect',
            'connect_timeout',
            'read_timeout',
            'algorithm',
            'consistent',
            'distributor',
        ]));

        if (! empty($options['password'])) {
            // @TODO: Remove after this will be implemented
            // https://github.com/phpredis/phpredis/issues/1508
            throw new InvalidArgumentException('RedisArray does not support authorization');
            //$client->auth((string) $options['password']);
        }

        if (! empty($options['database'])) {
            $client->select((int) $options['database']);
        }

        if (! empty($options['prefix'])) {
            $client->setOption(Redis::OPT_PREFIX, (string) $options['prefix']);
        }

        if (! empty($options['serializer'])) {
            $serializer = $this->getSerializerFromConfig($options['serializer']);
            $client->setOption(Redis::OPT_SERIALIZER, (string) $serializer);
        }

        return $client;
    }

    protected function getSerializerFromConfig(string $serializer): int
    {
        dump(get_class_methods(Redis::class));
        dump(get_class_vars(Redis::class));
        
        switch ($serializer) {
            default:
                $flag = Redis::SERIALIZER_NONE;
                break;
            case 'igbinary':
                $flag = Redis::SERIALIZER_IGBINARY;
                break;
            case 'php':
                $flag = Redis::SERIALIZER_PHP;
                break;
        }

        return $flag;
    }
}
