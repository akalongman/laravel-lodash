<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Auth\Passport\Grants;

use DateInterval;
use Laravel\Passport\Bridge\AccessToken;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestEvent;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Longman\LaravelLodash\Auth\Contracts\AuthServiceContract;
use Longman\LaravelLodash\Auth\Contracts\RefreshTokenBridgeRepositoryContract;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

use function is_null;

class InternalGrant extends Grant
{
    public const string IDENTIFIER = 'internal';

    public function __construct(
        protected readonly AuthServiceContract $authService,
        RefreshTokenBridgeRepositoryContract $refreshTokenRepository,
    ) {
        $this->setRefreshTokenRepository($refreshTokenRepository);
        $this->refreshTokenTTL = new DateInterval('P1M');
    }

    public function respondToAccessTokenRequest(
        ServerRequestInterface $request,
        ResponseTypeInterface $responseType,
        DateInterval $accessTokenTtl,
    ): ResponseTypeInterface {
        // Validate request
        $client = $this->validateClient($request);
        $scopes = $this->validateScopes($this->getRequestParameter('scope', $request));
        $user = $this->validateUser($request);

        // Finalize the requested scopes
        $scopes = $this->scopeRepository->finalizeScopes($scopes, $this->getIdentifier(), $client, $user->getIdentifier());

        // Issue and persist access token
        $accessToken = $this->issueAccessToken($accessTokenTtl, $client, $user->getIdentifier(), $scopes);
        $refreshToken = $this->issueRefreshToken($accessToken);

        // Inject access token into response type
        $responseType->setAccessToken($accessToken);
        $responseType->setRefreshToken($refreshToken);

        // Fire login event
        $this->authService->fireLoginEvent('api', $user);

        return $responseType;
    }

    public function getRefreshToken(AccessToken $token): void
    {
        $this->issueRefreshToken($token);
    }

    protected function validateUser(ServerRequestInterface $request): UserEntityInterface
    {
        $login = $this->getRequestParameter('login', $request);
        if (is_null($login)) {
            throw OAuthServerException::invalidRequest('login');
        }

        $password = $this->getRequestParameter('password', $request);
        if (is_null($password)) {
            throw OAuthServerException::invalidRequest('password');
        }

        try {
            $user = $this->authService->retrieveByCredentials(['login' => $login]);
        } catch (Throwable $e) {
            report($e);

            $this->getEmitter()->emit(new RequestEvent(RequestEvent::USER_AUTHENTICATION_FAILED, $request));

            throw OAuthServerException::invalidCredentials();
        }

        if (! $user instanceof UserEntityInterface) {
            $this->getEmitter()->emit(new RequestEvent(RequestEvent::USER_AUTHENTICATION_FAILED, $request));

            throw OAuthServerException::invalidCredentials();
        }

        if (! $this->authService->validateCredentials($user, $password)) {
            $this->getEmitter()->emit(new RequestEvent(RequestEvent::USER_AUTHENTICATION_FAILED, $request));

            throw OAuthServerException::invalidCredentials();
        }

        return $user;
    }
}
