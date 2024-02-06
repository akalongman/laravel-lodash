<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Support;

use Ramsey\Uuid\Exception\InvalidArgumentException;
use Ramsey\Uuid\Uuid as UuidFactory;

use function strtolower;

class Uuid
{
    public static function isBinary(string $value): bool
    {
        try {
            UuidFactory::fromBytes($value);
        } catch (InvalidArgumentException) {
            return false;
        }

        return true;
    }

    public static function toString(string $uuid): string
    {
        $uuid = UuidFactory::fromBytes($uuid);

        return $uuid->toString();
    }

    public static function toBinary(string $uuid): string
    {
        $uuid = UuidFactory::fromString(strtolower($uuid));

        return $uuid->getBytes();
    }
}
