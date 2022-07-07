<?php

declare(strict_types=1);

namespace App\Longman\LaravelLodash\Auth\Passport\Guards;

use App\Longman\LaravelLodash\Auth\Contracts\ClientRepositoryContract;
use App\Longman\LaravelLodash\Auth\Contracts\TokenRepositoryContract;
use Illuminate\Contracts\Encryption\Encrypter;
use Laravel\Passport\Guards\TokenGuard as BaseTokenGuard;
use Laravel\Passport\PassportUserProvider;
use League\OAuth2\Server\ResourceServer;

class TokenGuard extends BaseTokenGuard
{
    public function __construct(
        ResourceServer $server,
        PassportUserProvider $provider,
        TokenRepositoryContract $tokens,
        ClientRepositoryContract $clients,
        Encrypter $encrypter,
    ) {
        $this->server = $server;
        $this->tokens = $tokens;
        $this->clients = $clients;
        $this->provider = $provider;
        $this->encrypter = $encrypter;
    }
}
