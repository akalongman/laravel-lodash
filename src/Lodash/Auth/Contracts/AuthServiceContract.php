<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Auth\Contracts;

interface AuthServiceContract
{
    public function findOneForAuth(int | string $id): ?UserContract;

    public function findOneForAuthOrFail(int | string $id): UserContract;

    public function retrieveUserById(int | string $id): ?UserContract;

    public function updateAccessToken(string $accessTokenIdentifier, int $userId): void;

    public function updateRememberToken(UserContract $user, string $token): void;

    public function revokeToken(): void;

    public function revokeOtherTokens(): void;

    public function unsetUser(): void;

    public function getUser(): ?UserContract;

    public function isEmulating(): bool;

    public function getEmulatorUser(): ?UserContract;

    public function retrieveByCredentials(array $credentials): ?UserContract;

    public function retrieveUserByToken(int | string $identifier, string $token): ?UserContract;

    public function validateCredentials(UserContract $user, string $password): bool;

    public function rehashPasswordIfRequired(UserContract $user, array $credentials, bool $force = false): void;

    public function canUserEmulateOtherUser(UserContract $emulatorUser, UserContract $emulatedUser): bool;

    public function getGoogleUserByAccessToken(string $googleToken): ?UserContract;

    public function getGoogleUserByIdToken(string $googleToken): ?UserContract;

    public function fireLoginEvent(string $guard, UserContract $user, bool $remember = false): void;
}
