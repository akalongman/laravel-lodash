<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Redis\Connectors;

use Illuminate\Redis\Connections\PhpRedisClusterConnection;
use Illuminate\Redis\Connectors\PhpRedisConnector as BasePhpRedisConnector;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Redis as RedisFacade;
use InvalidArgumentException;
use LogicException;
use Redis;
use RedisArray;

use function array_map;
use function array_merge;
use function defined;

class PhpRedisConnector extends BasePhpRedisConnector
{
    public function connectToCluster(array $config, array $clusterOptions, array $options): PhpRedisClusterConnection
    {
        $options = array_merge($options, $clusterOptions, Arr::pull($config, 'options', []));

        // Use native Redis clustering
        if (Arr::get($options, 'cluster') === 'redis') {
            return new PhpRedisClusterConnection($this->createRedisClusterInstance(
                array_map([$this, 'buildClusterConnectionString'], $config),
                $options,
            ));
        }

        // Use client-side sharding
        return new PhpRedisClusterConnection($this->createRedisArrayInstance(
            array_map([$this, 'buildRedisArrayConnectionString'], $config),
            $options,
        ));
    }

    protected function createClient(array $config): Redis
    {
        return tap(new Redis(), function (Redis $client) use ($config) {
            if ($client instanceof RedisFacade) {
                throw new LogicException(
                    'Please remove or rename the Redis facade alias in your "app" configuration file in order to avoid collision with the PHP Redis extension.',
                );
            }

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

            if (empty($config['scan'])) {
                $client->setOption(Redis::OPT_SCAN, (string) Redis::SCAN_RETRY);
            }
        });
    }

    protected function buildRedisArrayConnectionString(array $server): string
    {
        return $server['host'] . ':' . $server['port'];
    }

    protected function createRedisArrayInstance(array $servers, array $options): RedisArray
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
        switch ($serializer) {
            case 'igbinary':
                if (! defined('Redis::SERIALIZER_IGBINARY')) {
                    throw new InvalidArgumentException('Error: phpredis was not compiled with igbinary support!');
                }
                $flag = Redis::SERIALIZER_IGBINARY;
                break;
            case 'php':
                $flag = Redis::SERIALIZER_PHP;
                break;
            default:
                $flag = Redis::SERIALIZER_NONE;
                break;
        }

        return $flag;
    }
}
