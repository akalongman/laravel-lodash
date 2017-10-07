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

use Blade;
use Illuminate\Support\ServiceProvider;
use Longman\LaravelMultiLang\Console\ExportCommand;
use Longman\LaravelMultiLang\Console\ImportCommand;
use Longman\LaravelMultiLang\Console\MigrationCommand;
use Longman\LaravelMultiLang\Console\TextsCommand;

class DebugServiceProvider extends ServiceProvider
{
    protected $defer = false;

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

    }

}
