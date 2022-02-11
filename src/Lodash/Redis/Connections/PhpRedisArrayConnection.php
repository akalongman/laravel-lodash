<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Redis\Connections;

use Illuminate\Redis\Connections\PhpRedisConnection;

class PhpRedisArrayConnection extends PhpRedisConnection
{
    /**
     * Flush the selected Redis database on all master nodes.
     *
     * @return mixed
     */
    public function flushdb()
    {
        $arguments = func_get_args();

        $async = strtoupper((string) ($arguments[0] ?? null)) === 'ASYNC';

        foreach ($this->client->_hosts() as $master) {
            $async
                ? $this->command('rawCommand', [$master, 'flushdb', 'async'])
                : $this->command('flushdb', [$master]);
        }
    }
}
