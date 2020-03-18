<?php

declare(strict_types=1);

namespace Longman\LaravelLodash;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Longman\LaravelLodash\Commands\ClearAll;
use Longman\LaravelLodash\Commands\DbClear;
use Longman\LaravelLodash\Commands\DbDump;
use Longman\LaravelLodash\Commands\DbRestore;
use Longman\LaravelLodash\Commands\LogClear;
use Longman\LaravelLodash\Commands\UserAdd;
use Longman\LaravelLodash\Commands\UserPassword;

use function array_keys;
use function array_pad;
use function preg_split;
use function trim;

class LodashServiceProvider extends ServiceProvider
{
    protected $commands = [
        'command.lodash.clear-all'     => ClearAll::class,
        'command.lodash.db.clear'      => DbClear::class,
        'command.lodash.db.dump'       => DbDump::class,
        'command.lodash.db.restore'    => DbRestore::class,
        'command.lodash.log.clear'     => LogClear::class,
        'command.lodash.user.add'      => UserAdd::class,
        'command.lodash.user.password' => UserPassword::class,
    ];

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/config.php' => config_path('lodash.php'),
        ]);

        $this->registerBladeDirectives();

        $this->loadTranslations();
    }

    public function register(): void
    {
        $this->registerCommands();

        $this->registerRequestMacros();
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
        app('blade.compiler')->directive('datetime', static function ($expression) {

            return "<?php echo '<time datetime=\'' . with({$expression})->toIso8601String()
                . '\' title=\'' . $expression . '\'>'
                . with({$expression})->diffForHumans() . '</time>' ?>";
        });

        // Pluralization helper
        app('blade.compiler')->directive('plural', static function ($expression) {
            $expression = trim($expression, '()');
            [$count, $str, $spacer] = array_pad(preg_split('/,\s*/', $expression), 3, "' '");

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

    protected function loadTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../translations', 'lodash');

        $this->publishes([
            __DIR__ . '/../translations' => resource_path('lang/vendor/lodash'),
        ]);
    }
}
