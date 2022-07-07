<?php

declare(strict_types=1);

namespace App\Longman\LaravelLodash\Auth\Contracts;

use Laravel\Passport\RefreshToken;

interface RefreshTokenRepositoryContract
{
    public function create($attributes);

    public function find($id);

    public function save(RefreshToken $token);

    public function revokeRefreshToken($id);

    public function revokeRefreshTokensByAccessTokenId($tokenId);

    public function isRefreshTokenRevoked($id);
}
