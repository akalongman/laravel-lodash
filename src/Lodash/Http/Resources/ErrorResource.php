<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Http\Resources;

class ErrorResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(array $resource)
    {
        $this->resource = $resource;

        $this->setDataWrapper('');
    }
}
