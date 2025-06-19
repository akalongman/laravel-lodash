<?php

declare(strict_types=1);

namespace Longman\LaravelLodash;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use InvalidArgumentException;
use Longman\LaravelLodash\Commands\ClearAll;
use Longman\LaravelLodash\Commands\DbClear;
use Longman\LaravelLodash\Commands\DbDump;
use Longman\LaravelLodash\Commands\DbRestore;
use Longman\LaravelLodash\Commands\LogClear;
use Longman\LaravelLodash\Commands\UserAdd;
use Longman\LaravelLodash\Commands\UserPassword;
use Longman\LaravelLodash\Validation\StrictTypeValidator;

use function app;
use function array_keys;
use function array_pad;
use function config;
use function config_path;
use function preg_split;
use function resource_path;
use function str_replace;
use function trim;

class ServiceProvider extends LaravelServiceProvider
{
    protected array $commands = [
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

        $this->loadValidations();
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'lodash');

        $this->registerCommands();

        $this->registerRequestMacros();
    }

    protected function registerCommands(): void
    {
        if (! config('lodash.register.commands')) {
            return;
        }

        foreach ($this->commands as $name => $class) {
            $this->app->singleton($name, $class);
        }

        $this->commands(array_keys($this->commands));
    }

    protected function registerBladeDirectives(): void
    {
        if (! config('lodash.register.blade_directives')) {
            return;
        }

        // Display relative time
        app('blade.compiler')->directive('datetime', static function ($expression) {
            return "<?php echo '<time datetime=\'' . with($expression)->toIso8601String()
                . '\' title=\'' . $expression . '\'>'
                . with($expression)->diffForHumans() . '</time>' ?>";
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
        if (! config('lodash.register.request_macros')) {
            return;
        }

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
        if (! config('lodash.register.translations')) {
            return;
        }

        $this->loadTranslationsFrom(__DIR__ . '/../translations', 'lodash');

        $this->publishes([
            __DIR__ . '/../translations' => resource_path('lang/vendor/lodash'),
        ]);
    }

    protected function loadValidations(): void
    {
        if (! config('lodash.register.validation_rules')) {
            return;
        }

        Validator::extend('strict', function (string $attribute, mixed $value, array $parameters, Validator $validator): bool {
            if (empty($parameters[0])) {
                throw new InvalidArgumentException('Strict rule requires an type argument');
            }

            $validator->addReplacer('strict', static function (string $message, string $attribute, string $rule, array $parameters): string {
                return str_replace(':type', $parameters[0], $message);
            });

            $customValidator = $this->app->make(StrictTypeValidator::class);

            return $customValidator->validate($attribute, $value, $parameters);
        }, 'The :attribute must be of type :type');
    }
}
