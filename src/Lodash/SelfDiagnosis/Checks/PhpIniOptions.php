<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\SelfDiagnosis\Checks;

use BeyondCode\SelfDiagnosis\Checks\Check;
use BeyondCode\SelfDiagnosis\Exceptions\InvalidConfigurationException;
use Illuminate\Support\Arr;
use Longman\LaravelLodash\SelfDiagnosis\ParsesNumValues;

use function count;
use function implode;
use function in_array;
use function ini_get;
use function preg_match;

use function trans;

use const PHP_EOL;

class PhpIniOptions implements Check
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
        return trans('lodash::checks.php_ini_options.name');
    }

    public function check(array $config): bool
    {
        $options = Arr::get($config, 'options', []);
        foreach ($options as $option => $value) {
            $actualValue = ini_get($option);
            $isMemoryMeasurement = in_array($option, ['memory_limit'], true);

            preg_match('#([><=]{0,2})(.*)#', $value, $match);

            if (! empty($match[1]) && $match[2] === '') {
                throw new InvalidConfigurationException('Value of option "' . $option . '" is invalid');
            }

            $prefix = $match[1] ?? null;
            switch ($prefix) {
                case '>':
                    $actualBytes = $this->toBytes($actualValue, $isMemoryMeasurement);
                    $bytes = $this->toBytes($match[2]);

                    if (! ($actualBytes > $bytes)) {
                        $this->options[$option] = $actualValue;
                    }
                    break;

                case '>=':
                    $actualBytes = $this->toBytes($actualValue, $isMemoryMeasurement);
                    $bytes = $this->toBytes($match[2]);

                    if (! ($actualBytes >= $bytes)) {
                        $this->options[$option] = $actualValue;
                    }
                    break;

                case '<':
                    $actualBytes = $this->toBytes($actualValue, $isMemoryMeasurement);
                    $bytes = $this->toBytes($match[2]);

                    if (! ($actualBytes < $bytes)) {
                        $this->options[$option] = $actualValue;
                    }
                    break;

                case '<=':
                    $actualBytes = $this->toBytes($actualValue, $isMemoryMeasurement);
                    $bytes = $this->toBytes($match[2]);

                    if (! ($actualBytes <= $bytes)) {
                        $this->options[$option] = $actualValue;
                    }
                    break;

                default:
                    if ($value !== $actualValue) {
                        $this->options[$option] = $actualValue;
                    }
                    break;
            }
        }

        return count($this->options) === 0;
    }

    public function message(array $config): string
    {
        $options = [];
        foreach ($this->options as $option => $value) {
            $options[] = $option . ': ' . $value;
        }

        return trans('lodash::checks.php_ini_options.message', [
            'options' => implode(PHP_EOL, $options),
        ]);
    }
}
