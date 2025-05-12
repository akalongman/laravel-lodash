<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Log;

use Illuminate\Log\Logger;
use Monolog\Formatter\LineFormatter;

/**
 * Add milliseconds to the log entry
 */
class DateTimeFormatter
{
    public function __invoke(Logger $logger): void
    {
        foreach ($logger->getLogger()->getHandlers() as $handler) {
            $handler->setFormatter(
                new LineFormatter(
                    LineFormatter::SIMPLE_FORMAT,
                    'Y-m-d H:i:s.u',
                ),
            );
        }
    }
}
