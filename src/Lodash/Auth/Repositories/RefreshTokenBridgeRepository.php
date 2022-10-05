<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Auth\Repositories;

use Laravel\Passport\Bridge\RefreshTokenRepository as BaseRefreshTokenRepository;
use Longman\LaravelLodash\Auth\Contracts\RefreshTokenBridgeRepositoryContract;

class RefreshTokenBridgeRepository extends BaseRefreshTokenRepository implements RefreshTokenBridgeRepositoryContract
{
    //
}
