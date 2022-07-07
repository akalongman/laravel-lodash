<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Auth\Repositories;

use Longman\LaravelLodash\Auth\Contracts\RefreshTokenBridgeRepositoryContract;
use Laravel\Passport\Bridge\RefreshTokenRepository as BaseRefreshTokenRepository;

class RefreshTokenBridgeRepository extends BaseRefreshTokenRepository implements RefreshTokenBridgeRepositoryContract
{
    //
}
