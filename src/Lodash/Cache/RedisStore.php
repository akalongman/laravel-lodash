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

namespace Longman\LaravelLodash\Cache;

use Illuminate\Cache\RedisStore as BaseRedisStore;

class RedisStore extends BaseRedisStore
{
    protected function serialize($value)
    {
        return $value;
    }

    protected function unserialize($value)
    {
        return $value;
    }
}
