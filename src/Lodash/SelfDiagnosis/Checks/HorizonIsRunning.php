<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\SelfDiagnosis\Checks;

use BeyondCode\SelfDiagnosis\Checks\Check;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Str;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

use function trans;

class HorizonIsRunning implements Check
{
    /** @var \Illuminate\Contracts\Console\Kernel */
    private $kernel;

    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    public function name(array $config): string
    {
        return trans('lodash::checks.horizon_is_running.name');
    }

    public function check(array $config): bool
    {
        $output = new BufferedOutput(
            OutputInterface::VERBOSITY_NORMAL,
            false,
        );

        $this->kernel->call('horizon:status', [], $output);
        $status = $output->fetch();
        if (Str::contains($status, 'inactive')) {
            return false;
        }

        return true;
    }

    public function message(array $config): string
    {
        return trans('lodash::checks.horizon_is_running.message');
    }
}
