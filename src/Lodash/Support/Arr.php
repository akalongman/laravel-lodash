<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Support;

use Illuminate\Support\Arr as BaseArr;

use function array_diff_key;
use function array_flip;
use function array_merge;

class Arr extends BaseArr
{
    public static function removeKeys(array $haystack, array ...$keys): array
    {
        return array_diff_key($haystack, array_flip(array_merge(...$keys)));
    }
}
