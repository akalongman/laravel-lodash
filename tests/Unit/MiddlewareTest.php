<?php
declare(strict_types=1);

namespace Tests\Unit;

use Longman\LaravelLodash\Middlewares\AllowCorsRequests;

class MiddlewareTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        /** @var \Illuminate\Routing\Router $router */
        $router = $app['router'];

        $router->get('url1', function () {
            return 'ok';
        })->middleware(AllowCorsRequests::class);
    }

    /** @test */
    public function it_should_return_error_on_invalid_origin()
    {
        config()->set('lodash.cors.allow_origins', ['domain.com']);

        // Invalid domain
        $response = $this->call('GET', 'url1', [], [], [], ['HTTP_Origin' => 'safsagsafsadsadsa']);
        $response->assertStatus(400); // Bad request

        // Without http://
        $response = $this->call('GET', 'url1', [], [], [], ['HTTP_Origin' => 'google.com']);
        $response->assertStatus(400); // Bad request

        // Valid domain, but not whitelisted
        $response = $this->call('GET', 'url1', [], [], [], ['HTTP_Origin' => 'http://google.com']);
        $response->assertStatus(405); // Method not allowed

        // Valid similar domain, but not whitelisted
        $response = $this->call('GET', 'url1', [], [], [], ['HTTP_Origin' => 'http://mydomain.com']);
        $response->assertStatus(405); // Method not allowed
    }

    /** @test */
    public function it_should_return_success_on_valid_origin()
    {
        config()->set('lodash.cors.allow_origins', ['domain.com']);

        $origins = config('lodash.cors.allow_origins');

        // Whitelisted origin
        $response = $this->call('GET', 'url1', [], [], [], ['HTTP_Origin' => 'http://' . $origins[0]]);
        $response->assertStatus(200);

        // Whitelisted subdomain origin
        $response = $this->call('GET', 'url1', [], [], [], ['HTTP_Origin' => 'http://sub.' . $origins[0]]);
        $response->assertStatus(200);
    }

    /** @test */
    public function it_should_return_success_on_project_url()
    {
        config()->set('lodash.cors.allow_origins', ['domain.com']);

        // Whitelisted origin
        $response = $this->call('GET', 'url1', [], [], [], ['HTTP_Origin' => config('app.url', 'http://localhost')]);
        $response->assertStatus(200);
    }
}
