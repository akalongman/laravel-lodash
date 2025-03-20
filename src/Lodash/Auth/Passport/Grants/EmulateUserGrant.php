<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Auth\Passport\Grants;

use DateInterval;
use Illuminate\Support\Arr;
use Laravel\Passport\Bridge\AccessToken;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\RequestEvent;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Longman\LaravelLodash\Auth\Contracts\AuthServiceContract;
use Longman\LaravelLodash\Auth\Events\StartEmulateEvent;
use Longman\LaravelLodash\Auth\Events\StopEmulateEvent;
use Psr\Http\Message\ServerRequestInterface;

use function event;

class EmulateUserGrant extends Grant
{
    public const string IDENTIFIER = 'emulate';

    public function __construct(
        protected readonly AuthServiceContract $authService,
        RefreshTokenRepositoryInterface $refreshTokenRepository,
    ) {
        $this->setRefreshTokenRepository($refreshTokenRepository);
        $this->refreshTokenTTL = new DateInterval('P1M');
    }

    public function respondToAccessTokenRequest(
        ServerRequestInterface $request,
        ResponseTypeInterface $responseType,
        DateInterval $accessTokenTtl,
    ): ResponseTypeInterface {
        $startEmulating = Arr::get($request->getAttributes(), 'startEmulating', false);

        $emulatedUser = $request->getAttribute('emulated_user');

        // Validate request
        $client = $this->validateClient($request);
        $scopes = $this->validateScopes($this->getRequestParameter('scope', $request));
        $emulatedUser = $this->validateEmulatedUser($emulatedUser, $request);

        // Finalize the requested scopes
        $scopes = $this->scopeRepository->finalizeScopes($scopes, $this->getIdentifier(), $client, $emulatedUser->getIdentifier());

        // Issue and persist access token
        $accessToken = $this->issueAccessToken($accessTokenTtl, $client, $emulatedUser->getIdentifier(), $scopes);
        $refreshToken = $this->issueRefreshToken($accessToken);

        if ($startEmulating) {
            $user = $request->getAttribute('user');

            event(new StartEmulateEvent($user, $emulatedUser));

            $user = $this->validateUser($user, $request, $emulatedUser);
            $this->authService->updateAccessToken($accessToken->getIdentifier(), $user->getId());
        } else {
            event(new StopEmulateEvent($emulatedUser));
        }

        // Inject access token into response type
        $responseType->setAccessToken($accessToken);
        $responseType->setRefreshToken($refreshToken);

        return $responseType;
    }

    public function getRefreshToken(AccessToken $token): void
    {
        $this->issueRefreshToken($token);
    }

    protected function validateUser(
        UserEntityInterface $user,
        ServerRequestInterface $request,
        UserEntityInterface $userEntity,
    ): UserEntityInterface {
        if (! $this->authService->canUserEmulateOtherUser($user, $userEntity)) {
            $this->getEmitter()->emit(new RequestEvent(RequestEvent::USER_AUTHENTICATION_FAILED, $request));

            throw OAuthServerException::accessDenied();
        }

        if (! $user instanceof UserEntityInterface) {
            $this->getEmitter()->emit(new RequestEvent(RequestEvent::USER_AUTHENTICATION_FAILED, $request));

            throw OAuthServerException::invalidCredentials();
        }

        return $user;
    }

    protected function validateEmulatedUser(
        UserEntityInterface $user,
        ServerRequestInterface $request,
    ): UserEntityInterface {
        return $user;
    }
}
