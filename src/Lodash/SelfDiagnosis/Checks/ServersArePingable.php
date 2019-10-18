<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\SelfDiagnosis\Checks;

use BeyondCode\SelfDiagnosis\Checks\Check;
use BeyondCode\SelfDiagnosis\Checks\ServersArePingable as BaseServersArePingable;
use BeyondCode\SelfDiagnosis\Exceptions\InvalidConfigurationException;
use BeyondCode\SelfDiagnosis\Server;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use JJG\Ping;

class ServersArePingable extends BaseServersArePingable implements Check
{
    /**
     * Perform the actual verification of this check.
     *
     * @param array $config
     * @return bool
     * @throws \BeyondCode\SelfDiagnosis\Exceptions\InvalidConfigurationException
     */
    public function check(array $config): bool
    {
        $this->notReachableServers = $this->parseConfiguredServers(Arr::get($config, 'servers', []));
        if ($this->notReachableServers->isEmpty()) {
            return true;
        }

        $this->notReachableServers = $this->notReachableServers->reject(static function (Server $server) {
            $ping = new Ping($server->getHost());
            $ping->setPort($server->getPort());
            $ping->setTimeout($server->getTimeout());

            if ($ping->getPort() === null) {
                $latency = $ping->ping('exec');
            } else {
                $latency = $ping->ping('fsockopen');
            }

            return $latency !== false;
        });

        return $this->notReachableServers->isEmpty();
    }

    private function parseConfiguredServers(array $servers): Collection
    {
        $result = new Collection();

        foreach ($servers as $server) {
            if (is_array($server)) {
                if (! empty(Arr::except($server, ['host', 'port', 'timeout']))) {
                    throw new InvalidConfigurationException('Servers in array notation may only contain a host, port and timeout parameter.');
                }
                if (! Arr::has($server, 'host')) {
                    throw new InvalidConfigurationException('For servers in array notation, the host parameter is required.');
                }

                $host = Arr::get($server, 'host');
                $port = Arr::get($server, 'port');
                $timeout = Arr::get($server, 'timeout', self::DEFAULT_TIMEOUT);

                $parsed = parse_url($host);
                $host = $parsed['host'] ?? $parsed['path'];
                if (empty($port)) {
                    $port = $parsed['port'] ?? null;
                }
                $result->push(new Server($host, $port, $timeout));
            } elseif (is_string($server)) {
                $result->push(new Server($server, null, self::DEFAULT_TIMEOUT));
            } else {
                throw new InvalidConfigurationException('The server configuration may only contain arrays or strings.');
            }
        }

        return $result;
    }
}
