<?php
/*
 * This file is part of the Laravel Lodash package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Longman\LaravelLodash\Queue;

use Longman\LaravelLodash\Queue\SqsFifo\Connectors\SqsFifoConnector;
use Illuminate\Queue\QueueManager;
use Illuminate\Queue\QueueServiceProvider as BaseQueueServiceProvider;

class QueueServiceProvider extends BaseQueueServiceProvider
{
    /**
     * Register the connectors on the queue manager.
     *
     * @param  \Illuminate\Queue\QueueManager  $manager
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
        $manager->addConnector('sqs.fifo', function (): SqsFifoConnector {
            return new SqsFifoConnector;
        });
    }
}
