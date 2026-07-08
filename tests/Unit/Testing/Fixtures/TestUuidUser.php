<?php

declare(strict_types=1);

namespace Tests\Unit\Testing\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Longman\LaravelLodash\Eloquent\UuidAsPrimaryContract;

class TestUuidUser extends Model implements UuidAsPrimaryContract
{
    protected $guarded = [];

    public function getUid(): string
    {
        return (string) $this->getAttribute('uid');
    }

    public function getUidString(): string
    {
        return (string) $this->getAttribute('uid');
    }

    public function isUuidBinary(string $value): bool
    {
        return false;
    }
}
