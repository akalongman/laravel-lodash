<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Debug;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

use function in_array;

class DebugServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $ips = config('lodash.debug.ips', []);
        $ip = app(Request::class)->getClientIp();
        if (! in_array($ip, $ips)) {
            return;
        }

        config(['app.debug' => true]);
    }

    public function register(): void
    {
        //
    }
}
