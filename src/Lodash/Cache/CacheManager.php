<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Cache;

use Illuminate\Cache\CacheManager as BaseCacheManager;

class CacheManager extends BaseCacheManager
{
    /**
     * Create an instance of the Redis cache driver.
     *
     * @param  array  $config
     * @return \Longman\LaravelLodash\Cache\RedisStore
     */
    protected function createRedisDriver(array $config)
    {
        $redis = $this->app['redis'];

        $connection = $config['connection'] ?? 'default';

        return $this->repository(new RedisStore($redis, $this->getPrefix($config), $connection));
    }
}
