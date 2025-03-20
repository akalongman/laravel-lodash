<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Auth\Services;

use Illuminate\Auth\AuthManager;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Support\ItemNotFoundException;
use InvalidArgumentException;
use Longman\LaravelLodash\Auth\Contracts\AuthServiceContract;
use Longman\LaravelLodash\Auth\Contracts\RefreshTokenRepositoryContract;
use Longman\LaravelLodash\Auth\Contracts\TokenRepositoryContract;
use Longman\LaravelLodash\Auth\Contracts\UserContract;
use Longman\LaravelLodash\Auth\Contracts\UserRepositoryContract;
use Longman\LaravelLodash\Auth\Passport\Guards\RequestGuard;

use function is_null;

readonly class AuthService implements AuthServiceContract
{
    public function __construct(
        protected RefreshTokenRepositoryContract $refreshTokenRepositoryContract,
        protected TokenRepositoryContract $tokenRepositoryContract,
        protected UserRepositoryContract $userRepositoryContract,
        protected AuthManager $authManager,
        protected HasherContract $hasher,
        protected Dispatcher $dispatcher,
    ) {
        //
    }

    public function findOneForAuth(int $id): ?UserContract
    {
        return $this->userRepositoryContract->findOneForAuth($id);
    }

    public function findOneForAuthOrFail(int $id): UserContract
    {
        $item = $this->findOneForAuth($id);
        if (! $item) {
            throw new ItemNotFoundException();
        }

        return $item;
    }

    public function retrieveUserById(int $id): ?UserContract
    {
        return $this->findOneForAuth($id);
    }

    public function updateAccessToken(string $accessTokenIdentifier, int $userId): void
    {
        $this->tokenRepositoryContract->update($accessTokenIdentifier, $userId);
    }

    public function updateRememberToken(UserContract $user, string $token): void
    {
        $this->userRepositoryContract->updateRememberToken($user, $token);
    }

    public function revokeToken(): void
    {
        if (! $this->authManager->guard() instanceof RequestGuard) {
            throw new InvalidArgumentException('Current guard is not the request guard');
        }

        if (is_null($this->authManager->user()->token())) {
            return;
        }

        /** @var \App\Models\User $user */
        $user = $this->authManager->user();

        $token = $user->token();
        $tokenId = $token->getKey();

        $token->revoke();

        $this->refreshTokenRepositoryContract->revokeRefreshTokensByAccessTokenId($tokenId);

        $this->unsetUser();
    }

    public function revokeOtherTokens(): void
    {
        if (! $this->authManager->guard() instanceof RequestGuard) {
            throw new InvalidArgumentException('Current guard is not request guard');
        }

        $user = $this->getUser();
        if (is_null($user->tokens)) {
            return;
        }

        /** @var \Laravel\Passport\Token $currentToken */
        $currentToken = $user->token();

        /** @var \Laravel\Passport\Token $token */
        foreach ($user->tokens as $token) {
            if ($currentToken->getKey() === $token->getKey()) {
                continue;
            }

            $tokenId = $token->getKey();
            $token->revoke();
            $this->refreshTokenRepositoryContract->revokeRefreshTokensByAccessTokenId($tokenId);
        }
    }

    public function unsetUser(): void
    {
        $this->authManager->unsetUser();
    }

    public function getUser(): ?UserContract
    {
        return $this->authManager->user();
    }

    public function isEmulating(): bool
    {
        if (! $this->getUser()) {
            return false;
        }

        if (! $this->authManager->guard() instanceof RequestGuard) {
            return false;
        }

        $token = $this->getUser()->token();
        $emulating = ! empty($token->emulator_user_id);

        return $emulating;
    }

    public function getEmulatorUser(): ?UserContract
    {
        if (! $this->isEmulating()) {
            return null;
        }

        $token = $this->getUser()->token();
        $emulatorId = $token->emulator_user_id;

        $user = $this->findOneForAuthOrFail($emulatorId);

        return $user;
    }

    public function retrieveByCredentials(array $credentials): ?UserContract
    {
        $user = $this->userRepositoryContract->retrieveByCredentials($credentials);
        if (! $user) {
            return null;
        }

        return $user;
    }

    public function retrieveUserByToken(int $identifier, string $token): ?UserContract
    {
        return $this->userRepositoryContract->retrieveUserByToken($identifier, $token);
    }

    public function validateCredentials(UserContract $user, string $password): bool
    {
        return $this->hasher->check($password, $user->getAuthPassword());
    }

    public function rehashPasswordIfRequired(UserContract $user, array $credentials, bool $force = false): void
    {
        if (! $this->hasher->needsRehash($user->getAuthPassword()) && ! $force) {
            return;
        }

        $user->forceFill([
            $user->getAuthPasswordName() => $this->hasher->make($credentials['password']),
        ])->save();
    }

    public function canUserEmulateOtherUser(UserContract $emulatorUser, UserContract $emulatedUser): bool
    {
        // Should be override in subclass
        return true;
    }

    public function getGoogleUserByAccessToken(string $googleToken): ?UserContract
    {
        $googleUser = $this->userRepositoryContract->getGoogleUserByAccessToken($googleToken);
        if (empty($googleUser)) {
            return null;
        }

        return $this->userRepositoryContract->retrieveByCredentials(['login' => $googleUser['email']]);
    }

    public function getGoogleUserByIdToken(string $googleToken): ?UserContract
    {
        $googleUser = $this->userRepositoryContract->getGoogleUserByIdToken($googleToken);
        if (empty($googleUser)) {
            return null;
        }

        return $this->userRepositoryContract->retrieveByCredentials(['login' => $googleUser['email']]);
    }

    public function fireLoginEvent(string $guard, UserContract $user, bool $remember = false): void
    {
        $this->dispatcher->dispatch(new Login($guard, $user, $remember));
    }
}
