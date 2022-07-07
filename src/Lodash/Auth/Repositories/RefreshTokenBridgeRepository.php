<?php

declare(strict_types=1);

namespace App\Longman\LaravelLodash\Auth\Repositories;

use App\Longman\LaravelLodash\Auth\Contracts\RefreshTokenBridgeRepositoryContract;
use Laravel\Passport\Bridge\RefreshTokenRepository as BaseRefreshTokenRepository;

class RefreshTokenBridgeRepository extends BaseRefreshTokenRepository implements RefreshTokenBridgeRepositoryContract
{
    //
}
