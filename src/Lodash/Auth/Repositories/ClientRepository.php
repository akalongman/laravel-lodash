<?php

declare(strict_types=1);

namespace App\Longman\LaravelLodash\Auth\Repositories;

use App\Longman\LaravelLodash\Auth\Contracts\ClientRepositoryContract;
use Laravel\Passport\ClientRepository as BaseClientRepository;

class ClientRepository extends BaseClientRepository implements ClientRepositoryContract
{
    //
}
