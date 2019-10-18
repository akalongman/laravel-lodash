<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\SelfDiagnosis\Checks;

use BeyondCode\SelfDiagnosis\Checks\Check;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Longman\LaravelLodash\SelfDiagnosis\ParsesNumValues;

class AvailableDiskSpace implements Check
{
    use ParsesNumValues;

    /** @var array */
    private $options = [];

    /**
     * The name of the check.
     *
     * @param array $config
     * @return string
     */
    public function name(array $config): string
    {
        return trans('lodash::checks.available_disk_space.name');
    }

    public function check(array $config): bool
    {
        $paths = Arr::get($config, 'paths', []);

        foreach ($paths as $path => $value) {
            $actual_space = disk_free_space($path);
            if ($actual_space === false) {
                throw new InvalidArgumentException('Can not get free space amount for path: ' . $path);
            }
            $actual_space = (int) $actual_space;
            $bytes = $this->toBytes($value);
            if ($actual_space >= $bytes) {
                continue;
            }

            $this->options[$path] = $actual_space;
        }

        return count($this->options) === 0;
    }

    public function message(array $config): string
    {
        $options = [];
        foreach ($this->options as $option => $value) {
            $options[] = '"' . $option . '": free space: ' . $this->fromBytes($value);
        }

        return trans('lodash::checks.available_disk_space.message', [
            'options' => implode(PHP_EOL, $options),
        ]);
    }
}
