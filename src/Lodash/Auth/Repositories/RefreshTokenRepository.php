<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Auth\Repositories;

use Laravel\Passport\RefreshTokenRepository as BaseRefreshTokenRepository;
use Longman\LaravelLodash\Auth\Contracts\RefreshTokenRepositoryContract;

class RefreshTokenRepository extends BaseRefreshTokenRepository implements RefreshTokenRepositoryContract
{
    //
}
