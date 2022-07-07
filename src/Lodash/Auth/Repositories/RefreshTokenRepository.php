<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Auth\Repositories;

use Longman\LaravelLodash\Auth\Contracts\RefreshTokenRepositoryContract;
use Laravel\Passport\RefreshTokenRepository as BaseRefreshTokenRepository;

class RefreshTokenRepository extends BaseRefreshTokenRepository implements RefreshTokenRepositoryContract
{
    //
}
