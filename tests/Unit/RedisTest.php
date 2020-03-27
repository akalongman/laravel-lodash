<?php

declare(strict_types=1);

namespace Tests\Unit;

use Longman\LaravelLodash\Redis\RedisManager;
use Redis;
use RedisArray;

use function getenv;
use function version_compare;

class RedisTest extends TestCase
{
    /** @test */
    public function it_should_set_custom_serializer()
    {
        $redis = $this->createConnection('phpredis', [
            'cluster' => false,
            'default' => [
                'host'         => getenv('REDIS_HOST') ?: '127.0.0.1',
                'port'         => getenv('REDIS_PORT') ?: 6379,
                'database'     => 5,
                'options'      => ['prefix' => 'lodash:'],
                'timeout'      => 0.5,
                'read_timeout' => 1.5,
                'serializer'   => 'igbinary',
            ],
        ]);
        /** @var \Redis $client */
        $client = $redis->connection()->client();

        $data = ['name' => 'Georgia'];
        $redis->set('country', $data, null, 60);

        $this->assertInstanceOf(Redis::class, $client);
        $this->assertEquals($client->getOption(Redis::OPT_SERIALIZER), Redis::SERIALIZER_IGBINARY);
        $this->assertEquals($redis->get('country'), $data);
    }

    /** @test */
    public function it_should_set_custom_serializer_for_cluster()
    {
        $redis = $this->createConnection('phpredis', [
            'clusters' => [
                'options' => [
                    'lazy_connect'    => true,
                    'connect_timeout' => 1,
                    'read_timeout'    => 3,
                    'password'        => getenv('REDIS_PASSWORD') ?: null,
                    'database'        => 5,
                    'prefix'          => 'lodash:',
                    'serializer'      => 'igbinary',
                ],

                'default' => [
                    [
                        'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
                        'port' => getenv('REDIS_PORT') ?: 6379,
                    ],
                ],
            ],
        ]);
        /** @var \RedisArray $client */
        $client = $redis->connection()->client();

        $data = ['name' => 'Georgia'];
        $redis->set('country2', $data, null, 60);

        $this->assertInstanceOf(RedisArray::class, $client);
        $this->assertEquals($redis->get('country2'), $data);
        foreach ($client->getOption(Redis::OPT_SERIALIZER) as $serializer) {
            $this->assertEquals($serializer, Redis::SERIALIZER_IGBINARY);
        }
    }

    private function createConnection(string $driver, array $config = []): RedisManager
    {
        if (version_compare($this->app->version(), '5.7', '<')) {
            $redis = new RedisManager($driver, $config);
        } else {
            $redis = new RedisManager($this->app, $driver, $config);
        }

        return $redis;
    }
}
