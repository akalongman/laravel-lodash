<?php
declare(strict_types=1);

namespace Tests\Unit;

use Longman\LaravelLodash\Redis\RedisManager;
use Redis;

class RedisTest extends TestCase
{
    /** @test */
    public function it_should_set_custom_serializer()
    {
        $redis = $this->createConnection('phpredis', [
            'default' => [
                'serializer' => 'igbinary',
            ],
        ]);
        $client = $redis->connection()->client();

        $data = ['name' => 'Georgia'];
        $redis->set('country', $data, null, 60);

        $this->assertEquals($client->getOption(Redis::OPT_SERIALIZER), Redis::SERIALIZER_IGBINARY);
        $this->assertEquals($redis->get('country'), $data);
    }

    public function createConnection(string $driver, array $config = []): RedisManager
    {
        $host = getenv('REDIS_HOST') ?: '127.0.0.1';
        $port = getenv('REDIS_PORT') ?: 6379;
        $defaultConfig = [
            'cluster' => false,
            'default' => [
                'host'         => $host,
                'port'         => $port,
                'database'     => 5,
                'options'      => ['prefix' => 'lodash:'],
                'timeout'      => 0.5,
                'read_timeout' => 1.5,
            ],
        ];
        $options = array_replace_recursive($defaultConfig, $config);

        if (version_compare($this->app->version(), '5.7', '<')) {
            $redis = new RedisManager($driver, $options);
        } else {
            $redis = new RedisManager($this->app, $driver, $options);
        }

        return $redis;
    }
}
