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

use Blade;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class LodashServiceProvider extends ServiceProvider
{
    protected $commands = [
        'command.lodash.clear-all'     => \Longman\LaravelLodash\Commands\ClearAll::class,
        'command.lodash.db.clear'      => \Longman\LaravelLodash\Commands\DbClear::class,
        'command.lodash.db.dump'       => \Longman\LaravelLodash\Commands\DbDump::class,
        'command.lodash.db.restore'    => \Longman\LaravelLodash\Commands\DbRestore::class,
        'command.lodash.log.clear'     => \Longman\LaravelLodash\Commands\LogClear::class,
        'command.lodash.user.add'      => \Longman\LaravelLodash\Commands\UserAdd::class,
        'command.lodash.user.password' => \Longman\LaravelLodash\Commands\UserPassword::class,

    ];

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/config.php' => config_path('lodash.php'),
        ]);
    }

    public function register(): void
    {
        $this->registerCommands();

        $this->registerRequestMacros();

        $this->registerBladeDirectives();
    }

    protected function registerCommands(): void
    {
        foreach ($this->commands as $name => $class) {
            $this->app->singleton($name, $class);
        }

        $this->commands(array_keys($this->commands));
    }

    protected function registerBladeDirectives(): void
    {
        // Display relative time
        Blade::directive('datetime', function ($expression) {

            return "<?php echo '<time datetime=\'' . with({$expression})->toIso8601String()
                . '\' title=\'' . $expression . '\'>'
                . with({$expression})->diffForHumans() . '</time>' ?>";
        });

        // Pluralization helper
        Blade::directive('plural', function ($expression) {
            $expression = trim($expression, '()');
            list($count, $str, $spacer) = array_pad(preg_split('/,\s*/', $expression), 3, "' '");

            return "<?php echo $count . $spacer . str_plural($str, $count) ?>";
        });
    }

    protected function registerRequestMacros(): void
    {
        Request::macro('getInt', function (string $name, int $default = 0): int {

            return (int) $this->get($name, $default);
        });

        Request::macro('getBool', function (string $name, bool $default = false): bool {

            return (bool) $this->get($name, $default);
        });

        Request::macro('getFloat', function (string $name, float $default = 0): float {

            return (float) $this->get($name, $default);
        });

        Request::macro('getString', function (string $name, string $default = ''): string {

            return (string) $this->get($name, $default);
        });
    }
}
