<?php
declare(strict_types=1);

namespace Tests\Unit\Middleware;

use Longman\LaravelLodash\Middlewares\AllowCorsRequests;
use Tests\Unit\TestCase;

use function config;
use function implode;

class AllowCorsRequestsTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        /** @var \Illuminate\Routing\Router $router */
        $router = $app['router'];

        $router->get('url1', static function () {
            return 'ok';
        })->middleware(AllowCorsRequests::class);

        $router->options('url1', static function () {
            return 'ok';
        })->middleware(AllowCorsRequests::class);
    }

    /** @test */
    public function it_should_return_correct_headers_on_options()
    {
        $origin = 'domain.com';
        config()->set('lodash.cors.allow_origins', [$origin]);
        $headers = [
            'Origin',
            'X-Requested-With',
            'Content-Type',
            'Accept',
            'Authorization',
            'CustomHeader1234',
        ];
        config()->set('lodash.cors.allow_headers', $headers);
        $methods = [
            'HEAD',
            'GET',
            'POST',
            'OPTIONS',
            'PUT',
            'PATCH',
            'DELETE',
            'CustomMethod1234',
        ];
        config()->set('lodash.cors.allow_methods', $methods);

        $response = $this->call('OPTIONS', 'url1', [], [], [], ['HTTP_Origin' => 'https://' . $origin]);
        $response->assertSeeText('Allowed');
        $response->assertHeader('Access-Control-Allow-Origin', 'https://' . $origin);
        $response->assertHeader('Access-Control-Allow-Credentials', 'true');
        $response->assertHeader('Access-Control-Allow-Methods', implode(',', $methods));
        $response->assertHeader('Access-Control-Allow-Headers', implode(',', $headers));
        $response->assertHeader('Access-Control-Max-Age', '1728000');
        $response->assertStatus(200);
    }

    /** @test */
    public function it_should_ignore_when_origin_missing()
    {
        $response = $this->call('GET', 'url1');
        $response->assertStatus(200);
    }

    /** @test */
    public function it_should_return_error_on_invalid_origin()
    {
        config()->set('lodash.cors.allow_origins', ['domain.com']);

        foreach (['GET', 'OPTIONS'] as $method) {
            // Invalid domain
            $response = $this->call($method, 'url1', [], [], [], ['HTTP_Origin' => 'safsagsafsadsadsa']);
            $response->assertStatus(400); // Bad request

            // Without http://
            $response = $this->call($method, 'url1', [], [], [], ['HTTP_Origin' => 'google.com']);
            $response->assertStatus(400); // Bad request

            // Valid domain, but not whitelisted
            $response = $this->call($method, 'url1', [], [], [], ['HTTP_Origin' => 'http://google.com']);
            $response->assertStatus(405); // Method not allowed

            // Valid similar domain, but not whitelisted
            $response = $this->call($method, 'url1', [], [], [], ['HTTP_Origin' => 'http://mydomain.com']);
            $response->assertStatus(405); // Method not allowed
        }
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
