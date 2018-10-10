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

namespace Longman\LaravelLodash\ElasticSearch;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class ElasticSearchServiceProvider extends ServiceProvider
{
    protected $defer = true;

    public function boot(): void
    {
        //
    }

    public function register(): void
    {
        $this->app->singleton(Client::class, function (Application $app) {
            // Logger instance
            $config = $app['config']->get('services.elastic_search');

            $params = [
                'hosts' => $config['hosts'],
            ];

            if (! empty($config['connectionParams'])) {
                $params['connectionParams'] = $config['connectionParams'];
            }

            $logger = ! empty($config['log_channel']) ? $app['log']->stack($config['log_channel']) : null;
            if ($logger) {
                $params['logger'] = $logger;
            }

            $client = ClientBuilder::fromConfig($params);

            return $client;
        });

        $this->app->singleton(ElasticSearchManagerContract::class, function (Application $app) {
            $client = $app->make(Client::class);
            $enabled = (bool) $app['config']->get('services.elastic_search.enabled', false);

            return new ElasticSearchManager($client, $enabled);
        });
    }

    public function provides()
    {
        return [Client::class, ElasticSearchManagerContract::class];
    }
}
