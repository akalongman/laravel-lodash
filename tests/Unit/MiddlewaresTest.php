<?php
declare(strict_types=1);

namespace Tests\Unit;

use Blade;
use Longman\LaravelLodash\Middlewares\AllowCorsRequests;
use Orchestra\Testbench\Http\Kernel;

class MiddlewaresTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        /** @var \Illuminate\Routing\Router $router */
        $router = $app['router'];

        /** @var \Orchestra\Testbench\Http\Kernel $kernel */
        $kernel = $app[Kernel::class];
        $kernel->pushMiddleware(AllowCorsRequests::class);

        $router->get('url1', function () {
            return 'ok';
        });

        $router->get('{all?}', function () {
            return 'all';
        })->where('all', '.*');
    }

    /** @test */
    public function it_should_return_error_on_invalid_origin()
    {
        $this->markTestSkipped();
        $response = $this->call('GET', 'url1', [], ['Origin' => 'safsagsafsadsadsa']);
        $response->dump();

        // Invalid domain
        $response = $this->json('OPTIONS', 'url1', [], ['Origin' => 'safsagsafsadsadsa']);
        $response->assertStatus(400); // Bad request

        // Without http://
        $response = $this->json('OPTIONS', 'url1', [], ['Origin' => 'google.com']);
        $response->assertStatus(400); // Bad request

        // Valid domain, but not whitelisted
        $response = $this->json('OPTIONS', 'url1', [], ['Origin' => 'http://google.com']);
        $response->assertStatus(405); // Method not allowed
    }

    /** @test */
    public function it_should_return_success_on_request()
    {
        $this->markTestSkipped();
        $origins = config('lodash.cors.allow_origins');
        if (empty($origins[0])) {
            $this->markTestSkipped('Allowed origins are not specified in config. Skip test.');
        }

        $origin = substr($origins[0], 4) !== 'http' ? 'http://' . $origins[0] : $origins[0];

        // Whitelisted origin
        $response = $this->json('OPTIONS', 'url1', [], ['Origin' => $origin]);
        $response->assertStatus(200);
    }
}
