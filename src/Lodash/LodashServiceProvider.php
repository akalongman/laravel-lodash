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

namespace Longman\LaravelLodash;

use Illuminate\Support\ServiceProvider;
use Longman\LaravelLodash\Commands\ClearAllCommand;

class LodashServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/config.php' => config_path('lodash.php'),
        ]);
    }

    public function register()
    {

        $this->app->singleton(
            'command.lodash.clear-all',
            function () {
                return new ClearAllCommand();
            }
        );

        $this->commands([
            'command.lodash.clear-all',
        ]);
    }
}
