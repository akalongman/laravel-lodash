<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Auth\Repositories;

use JetBrains\PhpStorm\Deprecated;
use Laravel\Passport\RefreshTokenRepository as BaseRefreshTokenRepository;
use Longman\LaravelLodash\Auth\Contracts\RefreshTokenRepositoryContract;

#[Deprecated('Use custom implementation')]
class RefreshTokenRepository extends BaseRefreshTokenRepository implements RefreshTokenRepositoryContract
{
    //
}
