<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Redis;

use Illuminate\Redis\Connectors\PredisConnector;
use Illuminate\Redis\RedisManager as BaseRedisManager;

class RedisManager extends BaseRedisManager
{
    /**
     * Get the connector instance for the current driver.
     *
     * @return \Longman\LaravelLodash\Redis\Connectors\PhpRedisConnector|\Illuminate\Redis\Connectors\PredisConnector
     */
    protected function connector()
    {
        switch ($this->driver) {
            case 'predis':
                return new PredisConnector();
            case 'phpredis':
                return new Connectors\PhpRedisConnector();
        }
    }
}
