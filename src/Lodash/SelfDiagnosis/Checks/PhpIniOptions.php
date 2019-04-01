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

namespace Longman\LaravelLodash\SelfDiagnosis\Checks;

use BeyondCode\SelfDiagnosis\Checks\Check;
use BeyondCode\SelfDiagnosis\Exceptions\InvalidConfigurationException;
use Illuminate\Support\Arr;
use Longman\LaravelLodash\SelfDiagnosis\ParsesNumValues;

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
            $actual_value = ini_get($option);

            preg_match('#([><=]{0,2})(.*)#', $value, $match);

            if (! empty($match[1]) && $match[2] === '') {
                throw new InvalidConfigurationException('Value of option "' . $option . '" is invalid');
            }

            $prefix = $match[1] ?? null;
            switch ($prefix) {
                default:
                    if ($value !== $actual_value) {
                        $this->options[$option] = $actual_value;
                    }
                    break;

                case '>':
                    $actual_bytes = $this->toBytes($actual_value);
                    $bytes = $this->toBytes($match[2]);

                    if (! ($actual_bytes > $bytes)) {
                        $this->options[$option] = $actual_value;
                    }
                    break;

                case '>=':
                    $actual_bytes = $this->toBytes($actual_value);
                    $bytes = $this->toBytes($match[2]);

                    if (! ($actual_bytes >= $bytes)) {
                        $this->options[$option] = $actual_value;
                    }
                    break;

                case '<':
                    $actual_bytes = $this->toBytes($actual_value);
                    $bytes = $this->toBytes($match[2]);

                    if (! ($actual_bytes < $bytes)) {
                        $this->options[$option] = $actual_value;
                    }
                    break;

                case '<=':
                    $actual_bytes = $this->toBytes($actual_value);
                    $bytes = $this->toBytes($match[2]);

                    if (! ($actual_bytes <= $bytes)) {
                        $this->options[$option] = $actual_value;
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
