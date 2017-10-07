<?php
declare(strict_types=1);

namespace Tests\Unit;

use GrahamCampbell\TestBenchCore\ServiceProviderTrait;

class ServiceProviderTest extends AbstractTestCase
{
    use ServiceProviderTrait;

    /**
     * @test
     */
    public function provides()
    {
        $this->testProvides();
    }
}
