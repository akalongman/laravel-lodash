<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Auth\Contracts;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;
use League\OAuth2\Server\Entities\UserEntityInterface;

interface UserContract extends Authenticatable, Authorizable, CanResetPassword, UserEntityInterface
{
    //
}
