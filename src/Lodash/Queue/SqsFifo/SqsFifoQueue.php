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

namespace Longman\LaravelLodash\Queue\SqsFifo;

use Aws\Sqs\SqsClient;
use BadMethodCallException;
use Illuminate\Queue\Jobs\SqsJob;
use Illuminate\Queue\SqsQueue;
use Illuminate\Support\Arr;

class SqsFifoQueue extends SqsQueue
{
    /**
     * @var array
     */
    private $options;

    /**
     * Create a new Amazon SQS queue instance.
     *
     * @param \Aws\Sqs\SqsClient $sqs
     * @param string $default
     * @param string $prefix
     * @param array $options
     */
    public function __construct(SqsClient $sqs, $default, $prefix = '', array $options = [])
    {
        $this->sqs = $sqs;
        $this->prefix = $prefix;
        $this->default = $default;
        $this->options = $options;

        if (Arr::get($this->options, 'polling') === 'long') {
            $this->sqs->setQueueAttributes([
                'Attributes' => [
                    'ReceiveMessageWaitTimeSeconds' => Arr::get($this->options, 'wait_time', 20),
                ],
                'QueueUrl'   => $this->getQueue($default),
            ]);
        }
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @see http://docs.aws.amazon.com/AWSSimpleQueueService/latest/SQSDeveloperGuide/general-recommendations.html
     * @see http://docs.aws.amazon.com/AWSSimpleQueueService/latest/SQSDeveloperGuide/FIFO-queue-recommendations.html
     *
     * @param  string $payload
     * @param  string $queue
     * @param  array $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $messageGroupId = $this->getMessageGroupId();
        $messageDeduplicationId = $this->getMessageDeduplicationId($payload);

        $messageId = $this->sqs->sendMessage([
            'QueueUrl'               => $this->getQueue($queue),
            'MessageBody'            => $payload,
            'MessageGroupId'         => $messageGroupId,
            'MessageDeduplicationId' => $messageDeduplicationId,
        ])->get('MessageId');

        return $messageId;
    }

    protected function getMessageGroupId(): string
    {
        $messageGroupId = session()->getId();
        if (empty($messageGroupId)) {
            $messageGroupId = str_random(40);
        }

        return $messageGroupId;
    }

    protected function getMessageDeduplicationId(string $payload): string
    {
        return config('app.debug') ? str_random(40) : sha1($payload);
    }

    /**
     * FIFO queues don't support per-message delays, only per-queue delays
     *
     * @see http://docs.aws.amazon.com/AWSSimpleQueueService/latest/SQSDeveloperGuide/FIFO-queues.html
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        throw new BadMethodCallException('FIFO queues don\'t support per-message delays, only per-queue delays');
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        $response = $this->sqs->receiveMessage([
            'QueueUrl'            => $queue = $this->getQueue($queue),
            'AttributeNames'      => ['ApproximateReceiveCount'],
            'MaxNumberOfMessages' => 1,
            'WaitTimeSeconds'     => Arr::get($this->options, 'wait_time', 20),
        ]);

        if (! is_null($response['Messages']) && count($response['Messages']) > 0) {
            return new SqsJob(
                $this->container,
                $this->sqs,
                $response['Messages'][0],
                $this->connectionName,
                $queue
            );
        }
    }
}
