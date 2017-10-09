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

class LodashServiceProvider extends ServiceProvider
{
    protected $commands = [
        'command.lodash.clear-all' => \Longman\LaravelLodash\Commands\ClearAll::class,
        'command.lodash.db.clear'  => \Longman\LaravelLodash\Commands\DbClear::class,
        'command.lodash.db.dump'   => \Longman\LaravelLodash\Commands\DbDump::class,
        'command.lodash.db.restore'   => \Longman\LaravelLodash\Commands\DbRestore::class,
        'command.lodash.log.clear' => \Longman\LaravelLodash\Commands\LogClear::class,

    ];


    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/config.php' => config_path('lodash.php'),
        ]);
    }

    public function register()
    {
        foreach ($this->commands as $name => $class) {
            $this->app->singleton($name, $class);
        }

        $this->commands(array_keys($this->commands));
    }
}
