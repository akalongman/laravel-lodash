<?php

declare(strict_types=1);

namespace Tests\Unit;

use Longman\LaravelLodash\Cache\CacheServiceProvider;
use Longman\LaravelLodash\Debug\DebugServiceProvider;
use Longman\LaravelLodash\Elasticsearch\ElasticsearchServiceProvider;
use Longman\LaravelLodash\LodashServiceProvider;
use Longman\LaravelLodash\Queue\QueueServiceProvider;
use Longman\LaravelLodash\Redis\RedisServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            LodashServiceProvider::class,
            CacheServiceProvider::class,
            DebugServiceProvider::class,
            ElasticsearchServiceProvider::class,
            QueueServiceProvider::class,
            RedisServiceProvider::class,
        ];
    }
}
