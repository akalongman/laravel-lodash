<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Auth\Repositories;

use JetBrains\PhpStorm\Deprecated;
use Laravel\Passport\Bridge\RefreshTokenRepository as BaseRefreshTokenRepository;
use Longman\LaravelLodash\Auth\Contracts\RefreshTokenBridgeRepositoryContract;

#[Deprecated('Use custom implementation')]
class RefreshTokenBridgeRepository extends BaseRefreshTokenRepository implements RefreshTokenBridgeRepositoryContract
{
    //
}
