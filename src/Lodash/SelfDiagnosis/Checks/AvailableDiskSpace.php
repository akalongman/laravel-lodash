<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\SelfDiagnosis\Checks;

use BeyondCode\SelfDiagnosis\Checks\Check;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Longman\LaravelLodash\SelfDiagnosis\ParsesNumValues;

use function count;
use function disk_free_space;
use function implode;

use const PHP_EOL;

class AvailableDiskSpace implements Check
{
    use ParsesNumValues;

    private array $options = [];

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
            $actualSpace = disk_free_space($path);
            if ($actualSpace === false) {
                throw new InvalidArgumentException('Can not get free space amount for path: ' . $path);
            }
            $actualSpace = (int) $actualSpace;
            $bytes = $this->toBytes($value);
            if ($actualSpace >= $bytes) {
                continue;
            }

            $this->options[$path] = $actualSpace;
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
