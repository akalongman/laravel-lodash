<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Queue\SqsFifo\Connectors;

use Aws\Sqs\SqsClient;
use Illuminate\Queue\Connectors\SqsConnector;
use Illuminate\Queue\SqsQueue;
use Illuminate\Support\Arr;
use Longman\LaravelLodash\Queue\SqsFifo\SqsFifoQueue;

class SqsFifoConnector extends SqsConnector
{
    /**
     * Establish a queue connection.
     *
     * @param array $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        $config = $this->getDefaultConfiguration($config);

        if ($config['key'] && $config['secret']) {
            $config['credentials'] = Arr::only($config, ['key', 'secret']);
        }

        $options = $config['options'] ?? [];
        unset($config['options']);

        $queue = Arr::get($options, 'type') === 'fifo' ? new SqsFifoQueue(
            new SqsClient($config),
            $config['queue'],
            $config['prefix'] ?? '',
            $options,
        ) : new SqsQueue(
            new SqsClient($config),
            $config['queue'],
            $config['prefix'] ?? '',
            $options,
        );

        return $queue;
    }
}
