<?php

declare(strict_types=1);

namespace Tests\Unit\Validation;

use BadMethodCallException;
use Illuminate\Support\Facades\Validator;
use Longman\LaravelLodash\Validation\Validator as LodashValidator;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\TestCase;

class ValidatorRegistrationTest extends TestCase
{
    #[Test]
    public function it_should_leave_the_stock_validator_when_disabled(): void
    {
        $validator = Validator::make(['value' => 5], ['value' => ['max:10']]);

        $this->assertNotInstanceOf(LodashValidator::class, $validator);
        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function it_should_not_register_the_strict_rule_when_disabled(): void
    {
        $this->expectException(BadMethodCallException::class);

        Validator::make(['value' => 5], ['value' => ['strict:integer']])->passes();
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('lodash.register.validation_rules', false);
    }
}
