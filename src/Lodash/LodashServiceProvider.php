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

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class LodashServiceProvider extends ServiceProvider
{
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
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'lodash');

        $this->registerCommands();

        $this->registerRequestMacros();
    }

    protected function registerCommands(): void
    {
        if (empty($commands = (array) config('lodash.available_commands'))) {
            return;
        }

        foreach ($commands as $name => $class) {
            $this->app->singleton($name, $class);
        }

        $this->commands(array_keys($commands));
    }

    protected function registerBladeDirectives(): void
    {
        $register_directives = config('lodash.register_blade_directives');

        if ($register_directives['datetime'] ?? false) {
            Blade::directive('datetime', function ($expression) {
                return "<?php echo '<time datetime=\'' . with({$expression})->toIso8601String()
                    . '\' title=\'' . $expression . '\'>'
                    . with({$expression})->diffForHumans() . '</time>' ?>";
            });
        }

      if ($register_directives['plural'] ?? false) {
            Blade::directive('plural', function ($expression) {
                $expression = trim($expression, '()');
                list($count, $str, $spacer) = array_pad(preg_split('/,\s*/', $expression), 3, "' '");
    
                return "<?php echo $count . $spacer . str_plural($str, $count) ?>";
            });
        }
    }

    protected function registerRequestMacros(): void
    {
        $register_request_macros = config('lodash.register_request_macros');

        if ($register_request_macros['getInt'] ?? false) {
            Request::macro('getInt', function (string $name, int $default = 0): int {
                return (int) $this->get($name, $default);
            });
        }

        if ($register_request_macros['getBool'] ?? false) {
            Request::macro('getBool', function (string $name, bool $default = false): bool {
                return (bool) $this->get($name, $default);
            });
        }

        if ($register_request_macros['getFloat'] ?? false) {
            Request::macro('getFloat', function (string $name, float $default = 0): float {
                return (float) $this->get($name, $default);
            });
        }

        if ($register_request_macros['getString'] ?? false) {
            Request::macro('getString', function (string $name, string $default = ''): string {
                return (string) $this->get($name, $default);
            });
        }
    }

    protected function loadTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../translations', 'lodash');

        $this->publishes([
            __DIR__ . '/../translations' => resource_path('lang/vendor/lodash'),
        ]);
    }
}
