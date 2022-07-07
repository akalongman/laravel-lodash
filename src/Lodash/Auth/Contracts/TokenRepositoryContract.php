<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Auth\Contracts;

use Laravel\Passport\Token;

interface TokenRepositoryContract
{
    public function create($attributes);

    public function find($id);

    public function findForUser($id, $userId);

    public function forUser($userId);

    public function getValidToken($user, $client);

    public function save(Token $token);

    public function revokeAccessToken($id);

    public function isAccessTokenRevoked($id);

    public function findValidToken($user, $client);

    public function update(string $accessTokenIdentifier, int $userId): void;
}
