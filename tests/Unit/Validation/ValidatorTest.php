<?php

declare(strict_types=1);

namespace Tests\Unit\Validation;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as IlluminateValidator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\TestCase;

use function trans;

class ValidatorTest extends TestCase
{
    #[Test]
    public function it_should_enforce_an_integer_max_bound_numerically(): void
    {
        $rules = ['value' => ['strict:integer', 'max:32767']];

        $this->assertTrue(Validator::make(['value' => 2147483648], $rules)->fails());
        $this->assertTrue(Validator::make(['value' => 32768], $rules)->fails());
        $this->assertTrue(Validator::make(['value' => 32767], $rules)->passes());
    }

    #[Test]
    public function it_should_enforce_an_integer_min_bound_numerically(): void
    {
        $rules = ['value' => ['strict:integer', 'min:1']];

        $this->assertTrue(Validator::make(['value' => 0], $rules)->fails());
        $this->assertTrue(Validator::make(['value' => -5], $rules)->fails());
        $this->assertTrue(Validator::make(['value' => 1], $rules)->passes());
    }

    #[Test]
    public function it_should_enforce_an_integer_range_numerically(): void
    {
        $rules = ['value' => ['strict:integer', 'between:1,10']];

        $this->assertTrue(Validator::make(['value' => 999], $rules)->fails());
        $this->assertTrue(Validator::make(['value' => 0], $rules)->fails());
        $this->assertTrue(Validator::make(['value' => 5], $rules)->passes());
    }

    #[Test]
    public function it_should_treat_exact_size_as_the_number_not_the_digit_count(): void
    {
        $rules = ['value' => ['strict:integer', 'size:5']];

        $this->assertTrue(Validator::make(['value' => 12345], $rules)->fails());
        $this->assertTrue(Validator::make(['value' => 5], $rules)->passes());
    }

    #[Test]
    public function it_should_enforce_a_float_max_bound_numerically(): void
    {
        $rules = ['value' => ['strict:float', 'max:5.5']];

        $this->assertTrue(Validator::make(['value' => 6.0], $rules)->fails());
        $this->assertTrue(Validator::make(['value' => 5.5], $rules)->passes());

        $this->assertTrue(Validator::make(['value' => 6.0], ['value' => ['strict:double', 'max:5.5']])->fails());
    }

    #[Test]
    public function it_should_report_the_numeric_message_variant_for_a_max_failure(): void
    {
        $validator = Validator::make(['value' => 2147483648], ['value' => ['strict:integer', 'max:32767']]);

        $message = $validator->errors()->first('value');

        $this->assertSame((string) trans('validation.max.numeric', ['attribute' => 'value', 'max' => '32767']), $message);
        $this->assertStringNotContainsString('characters', $message);
    }

    #[Test]
    public function it_should_behave_identically_when_laravels_own_integer_rule_is_present(): void
    {
        $rules = ['value' => ['strict:integer', 'integer', 'max:32767']];

        $this->assertTrue(Validator::make(['value' => 2147483648], $rules)->fails());
        $this->assertTrue(Validator::make(['value' => 32767], $rules)->passes());
    }

    #[Test]
    public function it_should_enforce_bounds_on_wildcard_attributes(): void
    {
        $validator = Validator::make(
            ['items' => [['qty' => 5], ['qty' => 500]]],
            ['items.*.qty' => ['strict:integer', 'max:100']],
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('items.1.qty'));
        $this->assertFalse($validator->errors()->has('items.0.qty'));
    }

    #[Test]
    public function it_should_keep_measuring_strict_strings_by_length(): void
    {
        $rules = ['value' => ['strict:string', 'max:5']];

        $this->assertTrue(Validator::make(['value' => '999999999'], $rules)->fails());
        $this->assertTrue(Validator::make(['value' => 'abcdef'], $rules)->fails());
        $this->assertTrue(Validator::make(['value' => 'abc'], $rules)->passes());

        $this->assertTrue(Validator::make(['value' => '12'], ['value' => ['strict:string', 'min:5']])->fails());
    }

    #[Test]
    public function it_should_leave_strict_boolean_attributes_untouched(): void
    {
        $this->assertTrue(Validator::make(['value' => true], ['value' => ['strict:boolean', 'max:1']])->passes());

        $validator = Validator::make(['value' => true], ['value' => ['strict:boolean', 'max:0']]);

        $this->assertTrue($validator->fails());
        $this->assertSame(
            (string) trans('validation.max.string', ['attribute' => 'value', 'max' => '0']),
            $validator->errors()->first('value'),
        );
    }

    #[Test]
    public function it_should_not_leak_numeric_mode_into_after_hooks(): void
    {
        $validator = Validator::make(
            ['b' => 'abcdefgh', 'a' => 999],
            ['b' => ['strict:string'], 'a' => ['strict:integer', 'max:5']],
        );

        $validator->after(static function (IlluminateValidator $v): void {
            $v->addFailure('b', 'Max', ['3']);
        });

        $this->assertTrue($validator->fails());
        $this->assertSame(
            (string) trans('validation.max.string', ['attribute' => 'b', 'max' => '3']),
            $validator->errors()->first('b'),
        );
    }

    #[Test]
    public function it_should_forward_custom_attribute_names_to_messages(): void
    {
        $validator = Validator::make(
            ['qty' => 2147483648],
            ['qty' => ['strict:integer', 'max:32767']],
            [],
            ['qty' => 'quantity'],
        );

        $this->assertTrue($validator->fails());
        $this->assertSame(
            (string) trans('validation.max.numeric', ['attribute' => 'quantity', 'max' => '32767']),
            $validator->errors()->first('qty'),
        );
    }

    #[Test]
    public function it_should_skip_a_bare_strict_rule_when_the_value_is_absent(): void
    {
        $this->assertTrue(Validator::make([], ['value' => ['strict']])->passes());
        $this->assertTrue(Validator::make(['value' => null], ['value' => ['strict']])->passes());
        $this->assertTrue(Validator::make(['value' => ''], ['value' => ['strict']])->passes());
    }

    #[Test]
    public function it_should_keep_length_semantics_without_a_strict_rule(): void
    {
        $this->assertTrue(Validator::make(['value' => '12'], ['value' => ['max:5']])->passes());
    }

    #[Test]
    public function it_should_pin_the_comparison_rules_as_numeric(): void
    {
        $cases = [
            ['gt:100', 50, 'validation.gt.numeric', '100'],
            ['gte:1', 0, 'validation.gte.numeric', '1'],
            ['lt:100', 100, 'validation.lt.numeric', '100'],
            ['lte:32767', 2147483648, 'validation.lte.numeric', '32767'],
        ];

        foreach ($cases as [$rule, $value, $key, $bound]) {
            $validator = Validator::make(['value' => $value], ['value' => ['strict:integer', $rule]]);

            $this->assertTrue($validator->fails(), $rule);
            $this->assertSame(
                (string) trans($key, ['attribute' => 'value', 'value' => $bound]),
                $validator->errors()->first('value'),
                $rule,
            );
        }
    }

    #[Test]
    public function it_should_gate_on_the_native_php_type(): void
    {
        $this->assertTrue(Validator::make(['value' => 5], ['value' => ['strict:integer']])->passes());
        $this->assertTrue(Validator::make(['value' => '5'], ['value' => ['strict:integer']])->fails());
        $this->assertTrue(Validator::make(['value' => 'abc'], ['value' => ['strict:string']])->passes());
    }

    #[Test]
    public function it_should_handle_an_attribute_whose_name_is_numeric(): void
    {
        // PHP converts a numeric rules key into an integer, so the attribute reaches the size hook as an int.
        $this->assertTrue(Validator::make(['5' => 2147483648], ['5' => ['strict:integer', 'max:32767']])->fails());
    }

    #[Test]
    public function it_should_throw_when_the_strict_rule_has_no_type_argument(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Validator::make(['value' => 5], ['value' => ['strict']])->passes();
    }
}
