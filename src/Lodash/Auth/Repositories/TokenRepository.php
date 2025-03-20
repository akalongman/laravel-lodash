<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Auth\Repositories;

use JetBrains\PhpStorm\Deprecated;
use Laravel\Passport\TokenRepository as BaseTokenRepository;
use Longman\LaravelLodash\Auth\Contracts\TokenRepositoryContract;

#[Deprecated('Use custom implementation')]
class TokenRepository extends BaseTokenRepository implements TokenRepositoryContract
{
    public function update(string $accessTokenIdentifier, int $userId): void
    {
        $token = $this->find($accessTokenIdentifier);
        $token->setAttribute('emulator_user_id', $userId);
        $this->save($token);
    }
}
