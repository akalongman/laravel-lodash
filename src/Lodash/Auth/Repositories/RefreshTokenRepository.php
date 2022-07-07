<?php

declare(strict_types=1);

namespace App\Longman\LaravelLodash\Auth\Repositories;

use App\Longman\LaravelLodash\Auth\Contracts\RefreshTokenRepositoryContract;
use Laravel\Passport\RefreshTokenRepository as BaseRefreshTokenRepository;

class RefreshTokenRepository extends BaseRefreshTokenRepository implements RefreshTokenRepositoryContract
{
    //
}
