<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Queue;

use Illuminate\Queue\QueueManager;
use Illuminate\Queue\QueueServiceProvider as BaseQueueServiceProvider;
use Longman\LaravelLodash\Queue\SqsFifo\Connectors\SqsFifoConnector;

class QueueServiceProvider extends BaseQueueServiceProvider
{
    /**
     * Register the connectors on the queue manager.
     *
     * @param  \Illuminate\Queue\QueueManager $manager
     * @return void
     */
    public function registerConnectors($manager)
    {
        foreach (['Null', 'Sync', 'Database', 'Redis', 'Beanstalkd', 'Sqs', 'SqsFifo'] as $connector) {
            $this->{"register{$connector}Connector"}($manager);
        }
    }

    protected function registerSqsFifoConnector(QueueManager $manager): void
    {
        $manager->addConnector('sqs.fifo', static function (): SqsFifoConnector {
            return new SqsFifoConnector();
        });
    }
}
