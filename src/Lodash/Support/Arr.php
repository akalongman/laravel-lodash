<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Support;

use Closure;
use Illuminate\Support\Arr as BaseArr;
use Traversable;

class Arr extends BaseArr
{
    public static function map(Traversable $array, Closure $function): array
    {
        $ret = [];
        foreach ($array as $key => $item) {
            $ret[$key] = $function($item, $key);
        }

        return $ret;
    }

    public static function undot(array $array): array
    {
        $result = [];
        foreach ($array as $item) {
            static::set($result, $item, []);
        }

        return $result;
    }
}
