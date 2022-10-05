<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider as LaravelUserProvider;
use Longman\LaravelLodash\Auth\Contracts\AuthServiceContract;

class InternalUserProvider implements LaravelUserProvider
{
    protected AuthServiceContract $authService;
    protected array $config;

    public function __construct(AuthServiceContract $authService, array $config)
    {
        $this->authService = $authService;
        $this->config = $config;
    }

    public function retrieveById($identifier)
    {
        return $this->authService->retrieveUserById((int) $identifier);
    }

    public function retrieveByToken($identifier, $token)
    {
        return $this->authService->retrieveUserByToken((int) $identifier, $token);
    }

    public function updateRememberToken(Authenticatable $user, $token)
    {
        $this->authService->updateRememberToken($user, $token);
    }

    public function retrieveByCredentials(array $credentials)
    {
        return $this->authService->retrieveByCredentials($credentials);
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        $plain = $credentials['password'];

        return $this->authService->validateCredentials($user, $plain);
    }
}
