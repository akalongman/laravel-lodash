<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Auth\Passport\Grants;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AbstractGrant;
use League\OAuth2\Server\RequestEvent;
use Override;
use Psr\Http\Message\ServerRequestInterface;

use function is_null;

abstract class Grant extends AbstractGrant
{
    public function getIdentifier(): string
    {
        return static::IDENTIFIER;
    }

    #[Override]
    protected function validateClient(ServerRequestInterface $request): ClientEntityInterface
    {
        $clientId = $this->getRequestParameter('client_id', $request);
        if (is_null($clientId)) {
            throw OAuthServerException::invalidRequest('client_id');
        }

        // Get client without validating secret
        $client = $this->clientRepository->getClientEntity($clientId);

        if (! $client instanceof ClientEntityInterface) {
            $this->getEmitter()->emit(new RequestEvent(RequestEvent::CLIENT_AUTHENTICATION_FAILED, $request));
            throw OAuthServerException::invalidClient($request);
        }

        return $client;
    }
}
