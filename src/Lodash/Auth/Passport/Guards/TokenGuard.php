<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Auth\Passport\Guards;

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Http\Request;
use Laravel\Passport\Guards\TokenGuard as BaseTokenGuard;
use Laravel\Passport\PassportUserProvider;
use League\OAuth2\Server\ResourceServer;
use Longman\LaravelLodash\Auth\Contracts\ClientRepositoryContract;
use Longman\LaravelLodash\Auth\Contracts\TokenRepositoryContract;

class TokenGuard extends BaseTokenGuard
{
    public function __construct(
        ResourceServer $server,
        PassportUserProvider $provider,
        TokenRepositoryContract $tokens,
        ClientRepositoryContract $clients,
        Encrypter $encrypter,
        Request $request,
    ) {
        $this->server = $server;
        $this->tokens = $tokens;
        $this->clients = $clients;
        $this->provider = $provider;
        $this->encrypter = $encrypter;
        $this->request = $request;
    }
}
