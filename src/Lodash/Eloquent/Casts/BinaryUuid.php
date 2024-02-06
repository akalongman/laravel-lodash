<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Longman\LaravelLodash\Support\Uuid;

class BinaryUuid implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (blank($value)) {
            return null;
        }

        return Uuid::toString($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if (blank($value)) {
            return null;
        }

        return [
            $key => Uuid::toBinary($value),
        ];
    }
}
