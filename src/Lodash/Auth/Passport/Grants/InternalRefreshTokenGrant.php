<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Auth\Passport\Grants;

use DateInterval;
use Laravel\Passport\TokenRepository;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\RequestAccessTokenEvent;
use League\OAuth2\Server\RequestEvent;
use League\OAuth2\Server\RequestRefreshTokenEvent;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Longman\LaravelLodash\Auth\Contracts\AuthServiceContract;
use Psr\Http\Message\ServerRequestInterface;

use function implode;
use function in_array;
use function is_null;

class InternalRefreshTokenGrant extends RefreshTokenGrant
{
    private readonly TokenRepository $tokenRepository;
    private readonly AuthServiceContract $authService;

    public function __construct(
        RefreshTokenRepositoryInterface $refreshTokenRepository,
        TokenRepository $tokenRepository,
        AuthServiceContract $authService,
    ) {
        parent::__construct($refreshTokenRepository);

        $this->tokenRepository = $tokenRepository;
        $this->authService = $authService;
    }

    public function getIdentifier(): string
    {
        return 'internal_refresh_token';
    }

    /**
     * {@inheritdoc}
     */
    public function respondToAccessTokenRequest(
        ServerRequestInterface $request,
        ResponseTypeInterface $responseType,
        DateInterval $accessTokenTTL,
    ) {
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
        $accessToken = $this->issueAccessToken($accessTokenTTL, $client, $oldRefreshToken['user_id'], $scopes);
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

        // when emulated user requests mew access token. set emulator user id.
        $oldAccessToken = $this->tokenRepository->find($oldRefreshToken['access_token_id']);
        if ($oldAccessToken->emulator_user_id) {
            $this->authService->updateAccessToken($accessToken->getIdentifier(), $oldAccessToken->emulator_user_id);
        }

        return $responseType;
    }

    protected function validateClient(ServerRequestInterface $request): ClientEntityInterface
    {
        [$basicAuthUser,] = $this->getBasicAuthCredentials($request);

        $clientId = $this->getRequestParameter('client_id', $request, $basicAuthUser);
        if (is_null($clientId)) {
            throw OAuthServerException::invalidRequest('client_id');
        }

        // Get client without validating secret
        $client = $this->clientRepository->getClientEntity($clientId);

        if ($client instanceof ClientEntityInterface === false) {
            $this->getEmitter()->emit(new RequestEvent(RequestEvent::CLIENT_AUTHENTICATION_FAILED, $request));
            throw OAuthServerException::invalidClient();
        }

        return $client;
    }
}
