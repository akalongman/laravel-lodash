<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Auth\Passport\Grants;

use DateInterval;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestAccessTokenEvent;
use League\OAuth2\Server\RequestEvent;
use League\OAuth2\Server\RequestRefreshTokenEvent;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Longman\LaravelLodash\Auth\Contracts\AuthServiceContract;
use Longman\LaravelLodash\Auth\Contracts\RefreshTokenBridgeRepositoryContract;
use Longman\LaravelLodash\Auth\Contracts\TokenRepositoryContract;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

use function implode;
use function in_array;
use function is_string;
use function json_decode;
use function time;

class InternalRefreshTokenGrant extends Grant
{
    public const string IDENTIFIER = 'internal_refresh_token';

    public function __construct(
        RefreshTokenBridgeRepositoryContract $refreshTokenRepository,
        private readonly TokenRepositoryContract $tokenRepository,
        private readonly AuthServiceContract $authService,
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
        $oldRefreshToken = $this->validateOldRefreshToken($request, $client->getIdentifier());
        $scopes = $this->validateScopes(
            $this->getRequestParameter(
                'scope',
                $request,
                implode(self::SCOPE_DELIMITER_STRING, $oldRefreshToken['scopes']),
            ),
        );

        // The OAuth spec says that a refreshed access token can have the original scopes or fewer so ensure
        // the request doesn't include any new scopes
        foreach ($scopes as $scope) {
            if (in_array($scope->getIdentifier(), $oldRefreshToken['scopes'], true) === false) {
                throw OAuthServerException::invalidScope($scope->getIdentifier());
            }
        }

        // Expire old tokens
        $this->accessTokenRepository->revokeAccessToken($oldRefreshToken['access_token_id']);
        if ($this->revokeRefreshTokens) {
            $this->refreshTokenRepository->revokeRefreshToken($oldRefreshToken['refresh_token_id']);
        }

        // Issue and persist new access token
        $accessToken = $this->issueAccessToken($accessTokenTtl, $client, $oldRefreshToken['user_id'], $scopes);
        $this->getEmitter()->emit(new RequestAccessTokenEvent(RequestEvent::ACCESS_TOKEN_ISSUED, $request, $accessToken));
        $responseType->setAccessToken($accessToken);

        // Issue and persist new refresh token if given
        if ($this->revokeRefreshTokens) {
            $refreshToken = $this->issueRefreshToken($accessToken);

            if ($refreshToken !== null) {
                $this->getEmitter()->emit(new RequestRefreshTokenEvent(RequestEvent::REFRESH_TOKEN_ISSUED, $request, $refreshToken));
                $responseType->setRefreshToken($refreshToken);
            }
        }

        // when emulated user requests new access token. set emulator user id.
        $oldAccessToken = $this->tokenRepository->find($oldRefreshToken['access_token_id']);
        if ($oldAccessToken->emulator_user_id) {
            $this->authService->updateAccessToken($accessToken->getIdentifier(), $oldAccessToken->emulator_user_id);
        }

        return $responseType;
    }

    protected function validateOldRefreshToken(ServerRequestInterface $request, $clientId)
    {
        $encryptedRefreshToken = $this->getRequestParameter('refresh_token', $request);
        if (! is_string($encryptedRefreshToken)) {
            throw OAuthServerException::invalidRequest('refresh_token');
        }

        // Validate refresh token
        try {
            $refreshToken = $this->decrypt($encryptedRefreshToken);
        } catch (Throwable $e) {
            throw OAuthServerException::invalidRefreshToken('Cannot decrypt the refresh token', $e);
        }

        $refreshTokenData = json_decode($refreshToken, true);
        if ($refreshTokenData['client_id'] !== $clientId) {
            $this->getEmitter()->emit(new RequestEvent(RequestEvent::REFRESH_TOKEN_CLIENT_FAILED, $request));
            throw OAuthServerException::invalidRefreshToken('Token is not linked to client');
        }

        if ($refreshTokenData['expire_time'] < time()) {
            throw OAuthServerException::invalidRefreshToken('Token has expired');
        }

        if ($this->refreshTokenRepository->isRefreshTokenRevoked($refreshTokenData['refresh_token_id']) === true) {
            throw OAuthServerException::invalidRefreshToken('Token has been revoked');
        }

        return $refreshTokenData;
    }
}
