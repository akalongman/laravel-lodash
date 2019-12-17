<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Redis;

use Illuminate\Redis\RedisServiceProvider as BaseRedisServiceProvider;
use Illuminate\Support\Arr;

class RedisServiceProvider extends BaseRedisServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('redis', static function ($app) {
            $config = $app->make('config')->get('database.redis');

            return new RedisManager($app, Arr::pull($config, 'client', 'phpredis'), $config);
        });

        $this->app->bind('redis.connection', static function ($app) {
            return $app['redis']->connection();
        });
    }
}
