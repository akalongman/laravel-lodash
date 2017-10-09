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

namespace Longman\LaravelLodash\Debug;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class DebugServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $ips = config('lodash.debug.ips');
        $ip = app(Request::class)->getClientIp();
        if (in_array($ip, $ips)) {
            config(['app.debug' => true]);
        }
    }

    public function register(): void
    {
    }
}
