<?php
declare(strict_types=1);

namespace Tests\Unit;

use Longman\LaravelLodash\LodashServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [LodashServiceProvider::class];
    }
}
