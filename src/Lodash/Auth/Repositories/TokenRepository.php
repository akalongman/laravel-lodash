<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Auth\Repositories;

use Longman\LaravelLodash\Auth\Contracts\TokenRepositoryContract;
use Laravel\Passport\TokenRepository as BaseTokenRepository;

class TokenRepository extends BaseTokenRepository implements TokenRepositoryContract
{
    public function update(string $accessTokenIdentifier, int $userId): void
    {
        $token = $this->find($accessTokenIdentifier);
        $token->setAttribute('emulator_user_id', $userId);
        $this->save($token);
    }
}
