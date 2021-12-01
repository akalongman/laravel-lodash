<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\SelfDiagnosis;

use JetBrains\PhpStorm\ArrayShape;

use function explode;
use function file_get_contents;
use function floor;
use function pow;
use function sprintf;
use function str_replace;
use function strlen;
use function strtolower;
use function trim;

trait ParsesNumValues
{
    private function toBytes(string $value, bool $measuringMemory = false): int
    {
        $value = trim($value);
        if ($measuringMemory && $value === '-1') {
            $info = $this->getSystemMemInfo();
            if ($info) {
                $value = $info['MemTotal'];
            }
        }

        $last = strtolower($value[strlen($value) - 1]);

        $value = (int) $value;
        switch ($last) {
            case 'g':
                $value *= 1024;
            // no break
            case 'm':
                $value *= 1024;
            // no break
            case 'k':
                $value *= 1024;
            // no break
        }

        return $value;
    }

    #[ArrayShape(['MemTotal' => 'string', 'MemFree' => 'string', 'MemAvailable' => 'string'])]
    private function getSystemMemInfo(): ?array
    {
        $meminfo = @file_get_contents('/proc/meminfo');
        if (! $meminfo) {
            return null;
        }
        $data = explode("\n", $meminfo);
        $meminfo = [];
        foreach ($data as $line) {
            if (empty($line)) {
                continue;
            }

            [$key, $val] = explode(':', $line);
            $meminfo[$key] = str_replace(['B', ' '], '', trim($val));
        }

        return $meminfo;
    }

    private function fromBytes(int $bytes, int $decimals = 2): string
    {
        $size = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $factor = (int) floor((strlen((string) $bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . $size[$factor];
    }
}
