<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Resources;

use Longman\LaravelLodash\Http\Resources\JsonResource;

class UserResource extends JsonResource
{
    protected static array $transformMapping = [
        'name'             => 'name',
        'mail'             => 'mail',
        'home_address'     => 'homeAddress',
        'calculated_field' => ['calculatedField' => 'getCalculatedField'],
    ];

    public function __construct(User $resource)
    {
        $this->resource = $resource;
    }
}
