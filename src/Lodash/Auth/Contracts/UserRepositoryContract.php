<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Auth\Contracts;

use League\OAuth2\Server\Repositories\UserRepositoryInterface;

interface UserRepositoryContract extends UserRepositoryInterface
{
    public function findOneForAuth(int | string $id): ?UserContract;

    public function getGoogleUserByAccessToken(string $googleToken): ?array;

    public function getGoogleUserByIdToken(string $googleToken): ?array;

    public function retrieveByCredentials(array $credentials): ?UserContract;

    public function retrieveUserByToken(int | string $identifier, string $token): ?UserContract;

    public function updateRememberToken(UserContract $user, string $token): void;
}
