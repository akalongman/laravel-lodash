<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Auth\Repositories;

use JetBrains\PhpStorm\Deprecated;
use Laravel\Passport\ClientRepository as BaseClientRepository;
use Longman\LaravelLodash\Auth\Contracts\ClientRepositoryContract;

#[Deprecated('Use custom implementation')]
class ClientRepository extends BaseClientRepository implements ClientRepositoryContract
{
    //
}
