<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\SelfDiagnosis\Checks;

use BeyondCode\SelfDiagnosis\Checks\Check;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;

use function collect;
use function trans;

class HorizonIsRunning implements Check
{
    /**
     * @var \Laravel\Horizon\Contracts\MasterSupervisorRepository
     */
    private $supervisorRepository;

    public function __construct(MasterSupervisorRepository $supervisorRepository)
    {
        $this->supervisorRepository = $supervisorRepository;
    }

    public function name(array $config): string
    {
        return trans('lodash::checks.horizon_is_running.name');
    }

    public function check(array $config): bool
    {
        if (! $masters = $this->supervisorRepository->all()) {
            return false;
        }

        return ! collect($masters)->contains(static function ($master) {
            return $master->status === 'paused';
        });
    }

    public function message(array $config): string
    {
        return trans('lodash::checks.horizon_is_running.message');
    }
}
