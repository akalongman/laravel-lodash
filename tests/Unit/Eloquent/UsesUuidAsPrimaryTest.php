<?php

declare(strict_types=1);

namespace Tests\Unit\Eloquent;

use Longman\LaravelLodash\Eloquent\UsesUuidAsPrimary;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Tests\Unit\TestCase;

class UsesUuidAsPrimaryTest extends TestCase
{
    #[Test]
    public function it_should_check_uuid_is_valid_binary(): void
    {
        $subject = new class {
            use UsesUuidAsPrimary;
        };

        $uuidString = '055a40ec-94a1-4cd7-891f-11604409055e';
        $uuid = Uuid::fromString($uuidString);
        $uuidBinary = $uuid->getBytes();

        $this->assertTrue($subject->isUuidBinary($uuidBinary));
    }

    #[Test]
    public function it_should_check_uuid_is_invalid_binary(): void
    {
        $subject = new class {
            use UsesUuidAsPrimary;
        };

        $uuidString = '055a40ec-94a1-4cd7-891f-11604409055e';
        $uuid = Uuid::fromString($uuidString);
        $uuidBinary = $uuid->toString(); // Not binary

        $this->assertFalse($subject->isUuidBinary($uuidBinary));
    }
}
