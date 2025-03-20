<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Auth\Repositories;

use App\Models\User;
use Exception;
use Google_Service_Oauth2;
use Illuminate\Database\Connection;
use JetBrains\PhpStorm\Deprecated;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use Longman\LaravelLodash\Auth\Contracts\UserContract;
use Longman\LaravelLodash\Auth\Contracts\UserRepositoryContract;
use RuntimeException;
use Throwable;

#[Deprecated('Use custom implementation')]
class UserRepository implements UserRepositoryContract
{
    protected Connection $database;
    protected Google_Service_Oauth2 $googleOauthService;

    public function __construct(Connection $database, Google_Service_Oauth2 $googleOauthService)
    {
        $this->database = $database;
        $this->googleOauthService = $googleOauthService;
    }

    public function findOneForAuth(int $id): ?UserContract
    {
        $item = $this->getModel()
            ->find($id);

        return $item;
    }

    public function getGoogleUserByAccessToken(string $googleToken): ?array
    {
        $this->googleOauthService
            ->getClient()
            ->setAccessToken($googleToken);

        $user = $this->googleOauthService->userinfo->get();
        if (empty($user->getEmail())) {
            return null;
        }

        return ['email' => $user->getEmail()];
    }

    public function getGoogleUserByIdToken(string $googleToken): ?array
    {
        try {
            $user = $this->googleOauthService
                ->getClient()
                ->verifyIdToken($googleToken);
        } catch (Throwable $e) {
            return null;
        }

        return $user;
    }

    public function getUserEntityByUserCredentials(
        $username,
        $password,
        $grantType,
        ClientEntityInterface $clientEntity,
    ) {
        throw new Exception('getUserEntityByUserCredentials is deprecated!');
    }

    public function retrieveByCredentials(array $credentials): ?UserContract
    {
        if (empty($credentials)) {
            return null;
        }

        $login = $credentials['login'] ?? $credentials['email'];

        $query = $this->getModel()->newQuery();

        $query->where('email', '=', $login);

        /** @var \App\Models\User $user */
        $user = $query->first();
        if (! $user) {
            return null;
        }

        return $user;
    }

    public function retrieveUserByToken(int $identifier, string $token): ?UserContract
    {
        throw new RuntimeException('Not implemented');
    }

    public function updateRememberToken(UserContract $user, string $token): void
    {
        // Not used
    }

    protected function getModel(): User
    {
        return new User();
    }
}
