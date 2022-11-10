<?php

declare(strict_types=1);

namespace Tests\Unit\Eloquent;

use Longman\LaravelLodash\Eloquent\UsesUuidAsPrimary;
use Ramsey\Uuid\Uuid;
use Tests\Unit\TestCase;

class UsesUuidAsPrimaryTest extends TestCase
{
    /** @test */
    public function it_should_check_uuid_is_valid_binary(): void
    {
        $mock = $this->getMockForTrait(UsesUuidAsPrimary::class);

        $uuidString = '055a40ec-94a1-4cd7-891f-11604409055e';
        $uuid = Uuid::fromString($uuidString);
        $uuidBinary = $uuid->getBytes();

        $this->assertTrue($mock->isUuidBinary($uuidBinary));
    }

    /** @test */
    public function it_should_check_uuid_is_invalid_binary(): void
    {
        $mock = $this->getMockForTrait(UsesUuidAsPrimary::class);

        $uuidString = '055a40ec-94a1-4cd7-891f-11604409055e';
        $uuid = Uuid::fromString($uuidString);
        $uuidBinary = $uuid->toString(); // Not binary

        $this->assertFalse($mock->isUuidBinary($uuidBinary));
    }
}
