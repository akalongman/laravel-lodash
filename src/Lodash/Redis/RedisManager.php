<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Redis;

use Illuminate\Redis\Connectors\PredisConnector;
use Illuminate\Redis\RedisManager as BaseRedisManager;
use InvalidArgumentException;
use Longman\LaravelLodash\Redis\Connectors\PhpRedisConnector;

class RedisManager extends BaseRedisManager
{
    /**
     * Get the connector instance for the current driver.
     *
     * @return \Longman\LaravelLodash\Redis\Connectors\PhpRedisConnector|\Illuminate\Redis\Connectors\PredisConnector
     */
    protected function connector(): PredisConnector|PhpRedisConnector
    {
        return match ($this->driver) {
            'predis'   => new PredisConnector(),
            'phpredis' => new PhpRedisConnector(),
            default    => throw new InvalidArgumentException('Redis driver ' . $this->driver . ' does not exists'),
        };
    }
}
