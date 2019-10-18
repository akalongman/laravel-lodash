<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use Longman\LaravelLodash\Middlewares\SimpleBasicAuth;
use Tests\Unit\TestCase;

use function config;

class SimpleBasicAuthTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        /** @var \Illuminate\Routing\Router $router */
        $router = $app['router'];

        $router->get('url1', static function () {
            return 'ok';
        })->middleware(SimpleBasicAuth::class);
    }

    /** @test */
    public function it_should_return_access_denied_on_empty_credentials()
    {
        config()->set('auth.simple', [
            'enabled'  => true,
            'user'     => 'testuser',
            'password' => 'testpass',
        ]);

        $response = $this->call('GET', 'url1', [], [], [], []);
        $response->assertStatus(401);
    }

    /** @test */
    public function it_should_return_access_denied_on_wrong_credentials()
    {
        config()->set('auth.simple', [
            'enabled'  => true,
            'user'     => 'testuser',
            'password' => 'testpass',
        ]);

        $response = $this->call('GET', 'url1', [], [], [], ['PHP_AUTH_USER' => 'testuser', 'PHP_AUTH_PW' => 'wrongpass']);
        $response->assertStatus(401);
    }

    /** @test */
    public function it_should_return_ok_on_disabled_auth()
    {
        config()->set('auth.simple', [
            'enabled'  => false,
            'user'     => 'testuser',
            'password' => 'testpass',
        ]);

        $response = $this->call('GET', 'url1', [], [], [], []);
        $response->assertStatus(200);
        $response->assertSeeText('ok');
    }

    /** @test */
    public function it_should_return_ok_with_credentials()
    {
        config()->set('auth.simple', [
            'enabled'  => true,
            'user'     => 'testuser',
            'password' => 'testpass',
        ]);
        $response = $this->call('GET', 'url1', [], [], [], ['PHP_AUTH_USER' => 'testuser', 'PHP_AUTH_PW' => 'testpass']);
        $response->assertStatus(200);
        $response->assertSeeText('ok');
    }
}
