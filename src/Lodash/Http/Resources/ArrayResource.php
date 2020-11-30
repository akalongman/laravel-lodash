<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Http\Resources;

class ArrayResource extends JsonResource
{
    public function __construct(array $resource)
    {
        $this->resource = $resource;
    }

    public function getTransformed(): array
    {
        return $this->resource;
    }
}
