<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Auth\Passport;

use DateInterval;
use Illuminate\Http\Request;
use Laravel\Passport\Passport;
use Laravel\Passport\PassportServiceProvider as BasePassportServiceProvider;
use Laravel\Passport\PassportUserProvider;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\ResourceServer;
use Longman\LaravelLodash\Auth\Contracts\AuthServiceContract;
use Longman\LaravelLodash\Auth\Contracts\ClientRepositoryContract;
use Longman\LaravelLodash\Auth\Contracts\RefreshTokenBridgeRepositoryContract;
use Longman\LaravelLodash\Auth\Contracts\RefreshTokenRepositoryContract;
use Longman\LaravelLodash\Auth\Contracts\TokenRepositoryContract;
use Longman\LaravelLodash\Auth\Contracts\UserRepositoryContract;
use Longman\LaravelLodash\Auth\Passport\Grants\EmulateUserGrant;
use Longman\LaravelLodash\Auth\Passport\Grants\GoogleAccessTokenGrant;
use Longman\LaravelLodash\Auth\Passport\Grants\GoogleIdTokenGrant;
use Longman\LaravelLodash\Auth\Passport\Grants\InternalGrant;
use Longman\LaravelLodash\Auth\Passport\Grants\InternalRefreshTokenGrant;
use Longman\LaravelLodash\Auth\Passport\Guards\RequestGuard;
use Longman\LaravelLodash\Auth\Passport\Guards\TokenGuard;
use Longman\LaravelLodash\Auth\Repositories\ClientRepository;
use Longman\LaravelLodash\Auth\Repositories\RefreshTokenBridgeRepository;
use Longman\LaravelLodash\Auth\Repositories\RefreshTokenRepository;
use Longman\LaravelLodash\Auth\Repositories\TokenRepository;
use Longman\LaravelLodash\Auth\Repositories\UserRepository;
use Longman\LaravelLodash\Auth\Services\AuthService;

use function tap;

class PassportServiceProvider extends BasePassportServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->registerCustomRepositories();
    }

    protected function registerAuthorizationServer(): void
    {
        $this->app->singleton(AuthorizationServer::class, function () {
            return tap($this->makeAuthorizationServer(), function (AuthorizationServer $server) {
                $accessTokenTtl = new DateInterval('PT1H');

                $server->setDefaultScope(Passport::$defaultScope);

                $server->enableGrantType($this->makeInternalGrant(), $accessTokenTtl);

                $server->enableGrantType($this->makeInternalRefreshTokenGrant(), $accessTokenTtl);

                $server->enableGrantType($this->makeGoogleAccessTokenGrant(), $accessTokenTtl);

                $server->enableGrantType($this->makeGoogleIdTokenGrant(), $accessTokenTtl);

                $server->enableGrantType($this->makeEmulateGrant(), $accessTokenTtl);
            });
        });
    }

    protected function makeGuard(array $config): RequestGuard
    {
        return new RequestGuard(function (Request $request) use ($config) {
            /** @var \Illuminate\Auth\AuthManager $authManager */
            $authManager = $this->app['auth'];

            return (new TokenGuard(
                $this->app->make(ResourceServer::class),
                new PassportUserProvider($authManager->createUserProvider($config['provider']), $config['provider']),
                $this->app->make(TokenRepositoryContract::class),
                $this->app->make(ClientRepositoryContract::class),
                $this->app->make('encrypter'),
                $request,
            ))->user();
        }, $this->app['request']);
    }

    protected function registerCustomRepositories(): void
    {
        $this->app->bind(ClientRepositoryContract::class, ClientRepository::class);
        $this->app->bind(TokenRepositoryContract::class, TokenRepository::class);
        $this->app->bind(RefreshTokenBridgeRepositoryContract::class, RefreshTokenBridgeRepository::class);
        $this->app->bind(UserRepositoryContract::class, UserRepository::class);
        $this->app->bind(RefreshTokenRepositoryContract::class, RefreshTokenRepository::class);
        $this->app->bind(AuthServiceContract::class, AuthService::class);
    }

    protected function makeInternalGrant(): InternalGrant
    {
        $grant = new InternalGrant(
            $this->app->make(AuthServiceContract::class),
            $this->app->make(RefreshTokenBridgeRepositoryContract::class),
        );

        $grant->setRefreshTokenTTL(new DateInterval('P1Y'));

        return $grant;
    }

    protected function makeInternalRefreshTokenGrant(): InternalRefreshTokenGrant
    {
        $repository = $this->app->make(RefreshTokenBridgeRepositoryContract::class);

        $grant = new InternalRefreshTokenGrant($repository);

        $grant->setRefreshTokenTTL(new DateInterval('P1Y'));

        return $grant;
    }

    protected function makeGoogleAccessTokenGrant(): GoogleAccessTokenGrant
    {
        $grant = new GoogleAccessTokenGrant(
            $this->app->make(AuthServiceContract::class),
            $this->app->make(RefreshTokenBridgeRepositoryContract::class),
        );

        $grant->setRefreshTokenTTL(new DateInterval('P1Y'));

        return $grant;
    }

    protected function makeGoogleIdTokenGrant(): GoogleIdTokenGrant
    {
        $grant = new GoogleIdTokenGrant(
            $this->app->make(AuthServiceContract::class),
            $this->app->make(RefreshTokenBridgeRepositoryContract::class),
        );

        $grant->setRefreshTokenTTL(new DateInterval('P1Y'));

        return $grant;
    }

    protected function makeEmulateGrant(): EmulateUserGrant
    {
        $grant = new EmulateUserGrant(
            $this->app->make(AuthServiceContract::class),
            $this->app->make(RefreshTokenBridgeRepository::class),
        );

        $grant->setRefreshTokenTTL(new DateInterval('P1Y'));

        return $grant;
    }
}
