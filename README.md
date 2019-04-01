# Laravel Lodash

[![Build Status](https://img.shields.io/travis/akalongman/laravel-lodash/master.svg?style=flat-square)](https://travis-ci.org/akalongman/laravel-lodash)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/akalongman/laravel-lodash.svg?style=flat-square)](https://scrutinizer-ci.com/g/akalongman/laravel-lodash/?branch=master)
[![Code Quality](https://img.shields.io/scrutinizer/g/akalongman/laravel-lodash.svg?style=flat-square)](https://scrutinizer-ci.com/g/akalongman/laravel-lodash/?branch=master)
[![Latest Stable Version](https://img.shields.io/github/tag/akalongman/laravel-lodash.svg?style=flat-square)](https://github.com/akalongman/laravel-lodash/tags)
[![Total Downloads](https://img.shields.io/packagist/dt/Longman/laravel-lodash.svg)](https://packagist.org/packages/longman/laravel-lodash)
[![Downloads Month](https://img.shields.io/packagist/dm/Longman/laravel-lodash.svg)](https://packagist.org/packages/longman/laravel-lodash)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

This package adds lot of useful functionality to the Laravel >=5.8 project

__Note:__ For Laravel older than 5.8 use the package version 1.*

## Table of Contents
- [Installation](#installation)
- [Usage](#usage)
    - [General](#general)
        - [Enable Debug Mode depending on visitor's IP Address](#enable-debug-mode-depending-on-visitors-ip-address)
        - [Add created_by, updated_by and deleted_by to the eloquent models](#add-created_by-updated_by-and-deleted_by-to-the-eloquent-models)
        - [Use UUID in the Eloquent Models](#use-uuid-in-the-eloquent-models)
        - [Eager loading of limited many to many relations via subquery or union](#eager-loading-of-limited-many-to-many-relations-via-subquery-or-union)
        - [Redis using igbinary](#redis-using-igbinary)
        - [Redis client side sharding](#redis-client-side-sharding)
        - [AWS SQS Fifo Queue](#aws-sqs-fifo-queue)
        - [Elasticsearch Integration](#elasticsearch-integration)
    - [Helper Functions](#helper-functions)
    - [Extended Classes](#extended-classes)
        - [Request Class](#request-class)
    - [Artisan Commands](#artisan-commands)
    - [Middleware List](#middleware-list)
    - [Blade Directives](#blade-directives)
    - [Misc](#misc)
        - [SelfDiagnosis Checks](#selfdiagnosis-checks)
- [TODO](#todo)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
- [License](#license)
- [Credits](#credits)

## Installation

Install this package through [Composer](https://getcomposer.org/).

Edit your project's `composer.json` file to require `longman/laravel-lodash`

Create *composer.json* file:
```json
{
    "name": "yourproject/yourproject",
    "type": "project",
    "require": {
        "longman/laravel-lodash": "^2.0"
    }
}
```
And run composer update

**Or** run a command in your command line:

    composer require longman/laravel-lodash

Add `LodashServiceProvider` to your service providers list in the `app.php`

```php
'providers' => [
    . . .
    /*
     * Package Service Providers...
     */
    Longman\LaravelLodash\LodashServiceProvider::class,
    . . .
],
```

Copy the package config and translations to your application with the publish command:

    php artisan vendor:publish --provider="Longman\LaravelLodash\LodashServiceProvider"


## Usage

### General

#### Enable Debug Mode depending on visitor's IP Address 

Add `Longman\LaravelLodash\Debug\DebugServiceProvider::class` in to `config/app.php`
and specify debug IP's in your `config/lodash.php` config file:
```php
    . . .
    'debug' => [
        'ips' => [ // IP list for enabling debug mode
            //'127.0.0.1',
        ],
    ],
    . . .
```

#### Add created_by, updated_by and deleted_by to the eloquent models 

Sometimes we need to know who created, updated or deleted entry in the database.

For this just add `Longman\LaravelLodash\Eloquent\UserIdentities` trait to your model and also
update migration file adding necessary columns:

```php
    $table->unsignedInteger('created_by')->nullable();
    $table->unsignedInteger('updated_by')->nullable();
    $table->unsignedInteger('deleted_by')->nullable();
```

#### Use UUID in the Eloquent Models

For this just add `Longman\LaravelLodash\Eloquent\UuidAsPrimary` trait to your model and also
update related migration file:

```php
    $table->uuid('id')->primary();
```

Also there is possible to specify uuid version via defining `uuidVersion` property in the model class.

#### Eager loading of limited many to many relations via subquery or union

Eager load many to many relations with limit via subquery or union. 
For using this feature, add `Longman\LaravelLodash\Eloquent\ManyToManyPreload` trait to the models.
After that you can use methods `limitPerGroupViaUnion()` and `limitPerGroupViaSubQuery()`.
For example you want to select users and 3 related user photos per user. 

```php
    $items = (new User)->with([
        'photos' => function (BelongsToMany $builder) {
            // Select via union. There you should pass pivot table fields array
            $builder->limitPerGroupViaUnion(3, ['user_id', 'photo_id']);
            // or
            // Select via subquery
            $builder->limitPerGroupViaSubQuery(3);
        }, 'other.relation1', 'other.relation2'
    ]);
    $items = $items->get();
```

Now each user model have 3 photos model selected via one query. 
You can specify additional where clauses or order by fields before the group method call.

#### Redis using igbinary

Igbinary is a drop in replacement for the standard php serializer. 
Igbinary stores php data structures in compact binary form. 
Savings are significant when using Redis or similar memory based storages for serialized data.
Via Igbinary repetitive strings are stored only once. Collections of Eloquent objects benefit significantly from this.

By default Laravel does not provide an option to enable igbinary serializer for PhpRedis connection 
and you have to use LaravelLodash implementation for this.

First of all, make sure you enabled PhpRedis driver by this guide https://laravel.com/docs/5.5/redis#phpredis

After that include Cache and Redis service providers in the `app.php` before your App providers:
```php
    . . .
    Longman\LaravelLodash\Cache\CacheServiceProvider::class,
    Longman\LaravelLodash\Redis\RedisServiceProvider::class,
    . . .
```
You can remove Laravel's Cache and Redis service providers from the config, 
because LaravelLodash providers are extended from them and therefore implements entire functional.

Now you can specify the serializer in your `database.php` under `config` folder:

Also, you can specify other options like `scan` or etc. See https://github.com/phpredis/phpredis#setoption

#### Redis client side sharding

PhpRedis extension along with native Redis Cluster, also supports [client-side sharding](https://github.com/phpredis/phpredis/blob/develop/arrays.markdown#readme).
This feature is very useful, when you want distribute your data between multiple servers, but do not want use native Redis Cluster.

Its not implemented in the Laravel by default. We tried to fix this :smile:

Config example:

```php
    . . .
    'redis' => [
        'client' => 'phpredis',
        
        'clusters' => [
            'options' => [
                'lazy_connect'    => true,
                'connect_timeout' => 1,
                'read_timeout'    => 3,
                'password'        => env('REDIS_PASSWORD', null),
                'database'        => env('REDIS_DATABASE', 0),
                'prefix'          => env('REDIS_PREFIX'),
                'serializer'      => 'igbinary',
            ],

            'default' => [
                [
                    'host' => env('REDIS_SHARD1_HOST', '127.0.0.1'),
                    'port' => env('REDIS_SHARD1_PORT', 6379),
                ],
                [
                    'host' => env('REDIS_SHARD2_HOST', '127.0.0.2'),
                    'port' => env('REDIS_SHARD2_PORT', 6379),
                ],
                . . .
            ],
        ],
    ],
    . . .
```

#### AWS SQS Fifo Queue

Laravel by default does not supports AWS FIFO queues and this package fixes it.

You have to add `QueueServiceProvider` service provider in the `app.php` before your App providers:
```php
    . . .
    Longman\LaravelLodash\Queue\QueueServiceProvider::class,
    . . .
```
You can remove Laravel's Queue service provider from the config, 
because LaravelLodash provider are extended from that and therefore implements entire functional.

Now you can add the new connection in the `queue.php` under `config` folder:

```php
    . . .
    'sqs_fifo' => [
        'driver'  => 'sqs.fifo',
        'version' => 'latest',
        'key'     => env('AWS_ACCESS_KEY_ID'),
        'secret'  => env('AWS_SECRET_ACCESS_KEY'),
        'prefix'  => env('AWS_SQS_URL'),
        'queue'   => env('AWS_SQS_DEFAULT_QUEUE'),
        'region'  => env('AWS_REGION'),
        'options' => [
            'type'      => 'fifo', // fifo, normal
            'polling'   => 'long', // long, short
            'wait_time' => 20,
        ],
    ],
    . . .
```

#### Elasticsearch Integration

First of all you have to install official elasticsearch php sdk:

    composer require elasticsearch/elasticsearch

After add `ElasticsearchServiceProvider` service provider in the `app.php` before your App providers:
```php
    . . .
    Longman\LaravelLodash\Elasticsearch\ElasticsearchServiceProvider::class,
    . . .
```
Now you can add the configuration in the `services.php` under `config` folder:

```php
    . . .
    'elasticsearch' => [
        'enabled'          => env('ELASTICSEARCH_ENABLED', false),
        'log_channel'      => ['daily'],
        'hosts'            => [
            [
                'host' => env('ELASTICSEARCH_HOST', 'localhost'),
                'port' => env('ELASTICSEARCH_PORT', 9200),
            ],
        ],
        'connectionParams' => [
            'client' => [
                'timeout'         => env('ELASTICSEARCH_TIMEOUT', 3),
                'connect_timeout' => env('ELASTICSEARCH_TIMEOUT', 3),
            ],
        ],
    ],
    . . .
```

You can use Elasticsearch integration via 

```php
    $elasticsearch_manager = app(ElasticsearchManagerContract::class);
    
    // Call wrapped methods
    $elasticsearch_manager->createIndex('some-index');
    
    // Or get native client and access their methods
    $client = $elasticsearch_manager->getClient();
    $client->indices()->create($params);

```

Also you can perform search via searchable query object. Just create class and 
implement `ElasticsearchQueryContract` and you can pass object to `performSearch` method

```php

    $elasticsearch_manager = app(ElasticsearchManagerContract::class);
    $results = $elasticsearch_manager->performSearch($query); 

```

### Helper Functions

Function  | Description
------------- | -------------
`p(...$values): void`  |  Add debug messages to the debugbar
`get_db_query(): ?string`  |  Get last executed database query
`get_db_queries(): ?array`  |  Get all executed database queries

### Extended Classes

For this fuctional you should add `Longman\LaravelLodash\LodashServiceProvider::class` in the `config/app.php` file.

There is an extended classes via Laravel's builtin macros functionality

#### Request class

Method  | Description
------------- | -------------
`getInt(string $name, int $default = 0): int`  |  Return request field value as a integer
`getBool(string $name, bool $default = false): bool`  |  Return request field value as a boolean
`getFloat(string $name, float $default = 0): float`  |  Return request field value as a float
`getString(string $name, string $default = ''): string`  |  Return request field value as a string

### Artisan Commands

For this fuctional you should add `Longman\LaravelLodash\LodashServiceProvider::class` in the `config/app.php` file.

Command  | Description
------------- | -------------
`php artisan clear-all`  |  Clear entire cache and all cached routes, views, etc.
`php artisan db:clear`  |  Drop all tables from database. Options:<br/>--database= : The database connection to use.<br/>--force : Force the operation to run when in production.<br/>--pretend : Dump the SQL queries that would be run.
`php artisan db:dump`  |  Dump database to sql file using mysqldump CLI utility. Options:<br/>--database= : The database connection to use.<br/>--path= : Folder path for store database dump files.
`php artisan db:restore {file}`  |  Restore database from sql file using mysqldump CLI utility. Options:<br/>--database= : The database connection to use.<br/>--force : Force the operation to run when in production
`php artisan log:clear`  |  Clear log files from `storage/logs` recursively. Options:<br/>--force : Force the operation to run when in production.
`php artisan user:add {email} {password?}`  |  Create a new user. Options:<br/>--guard= : The guard to use.
`php artisan user:password {email} {password?}`  |  Update/reset user password. Options:<br/>--guard= : The guard to use.


### Middleware List

Middleware  | Description
------------- | -------------
`AllowCorsRequests`  |  Allows cross origin requests. Can be configured allowed hosts, methods and headers in the configuration file
`XssSecurity`  |  Sets XSS Security headers. Can be configured excluded URI-s, etc.

### Blade Directives

For this functional you should add `Longman\LaravelLodash\LodashServiceProvider::class` in the `config/app.php` file.

Directive  | Description
------------- | -------------
`@datetime($date);`  |  Display relative time. Example:<br/>`$date = Carbon\Carbon::now();`<br />`@datetime($date);`
`@plural($count, $word)`  |  Pluralization helper. Example:<br/>`@plural(count($posts), 'post')`<br />Produces '1 post' or '2 posts', depending on how many items in $posts there are


### Misc

#### SelfDiagnosis Checks

For using this checks, you have to install the package: [laravel-self-diagnosis](https://github.com/beyondcode/laravel-self-diagnosis)

#### Available Disk Space Check

```php
...
\Longman\LaravelLodash\SelfDiagnosis\Checks\AvailableDiskSpace::class => [
    'paths' => [
        '/' => '100G', // At least 100G should be available for the path "/"
        '/var/www' => '5G',
    ],
],
...
```

#### Elasticsearch Health Check

```php
...
\Longman\LaravelLodash\SelfDiagnosis\Checks\ElasticsearchCanBeAccessed::class => [
    'client' => ElasticSearchClient::class,
],
...
```

#### Php Ini Options Check

```php
...
\Longman\LaravelLodash\SelfDiagnosis\Checks\PhpIniOptions::class => [
    'options' => [
        'upload_max_filesize' => '>=128M',
        'post_max_size'       => '>=128M',
        'memory_limit'        => '>=128M',
        'max_input_vars'      => '>=10000',
        'file_uploads'        => '1',
        'disable_functions'   => '',
    ],
],
...
```

#### Php Ini Options Check

```php
...
\Longman\LaravelLodash\SelfDiagnosis\Checks\RedisCanBeAccessed::class => [
    'default_connection' => true,
    'connections'        => ['sessions'],
],
...
```

#### Servers Are Pingable Check

```php
...
\Longman\LaravelLodash\SelfDiagnosis\Checks\ServersArePingable::class => [
    'servers' => [
        [
            'host'    => config('app.url'),
            'port'    => 80,
            'timeout' => 1,
        ],
        [
            'host'    => 'sqs.eu-west-1.amazonaws.com',
            'port'    => 443,
            'timeout' => 3,
        ],
        [
            'host'    => 'www.googleapis.com',
            'port'    => 443,
            'timeout' => 3,
        ],
    ],
],
...
```

## TODO

write more tests and add more features

## Troubleshooting

If you like living on the edge, please report any bugs you find on the
[laravel-lodash issues](https://github.com/akalongman/laravel-lodash/issues) page.

## Contributing

Pull requests are welcome.
See [CONTRIBUTING.md](CONTRIBUTING.md) for information.

## License

Please see the [LICENSE](LICENSE.md) included in this repository for a full copy of the MIT license,
which this project is licensed under.

## Credits

- [Avtandil Kikabidze aka LONGMAN](https://github.com/akalongman)

Full credit list in [Contributors](https://github.com/akalongman/laravel-lodash/graphs/contributors)
