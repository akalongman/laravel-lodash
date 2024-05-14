<?php

declare(strict_types=1);

namespace Tests\Unit;

use Longman\LaravelLodash\Cache\CacheServiceProvider;
use Longman\LaravelLodash\Debug\DebugServiceProvider;
use Longman\LaravelLodash\Elasticsearch\ElasticsearchServiceProvider;
use Longman\LaravelLodash\Queue\QueueServiceProvider;
use Longman\LaravelLodash\Redis\RedisServiceProvider;
use Longman\LaravelLodash\ServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ServiceProvider::class,
            CacheServiceProvider::class,
            DebugServiceProvider::class,
            ElasticsearchServiceProvider::class,
            QueueServiceProvider::class,
            RedisServiceProvider::class,
        ];
    }
}
