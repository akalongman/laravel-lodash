<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Cache;

use Illuminate\Cache\MemcachedConnector;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\ServiceProvider;

class CacheServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton('cache', static function ($app) {
            return new CacheManager($app);
        });

        $this->app->singleton('cache.store', static function ($app) {
            return $app['cache']->driver();
        });

        $this->app->singleton('memcached.connector', static function () {
            return new MemcachedConnector();
        });

        $this->app->singleton(RateLimiter::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'cache',
            'cache.store',
            'memcached.connector',
            RateLimiter::class,
        ];
    }
}
