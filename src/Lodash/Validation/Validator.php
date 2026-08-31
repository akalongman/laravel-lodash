<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Validation;

use Illuminate\Validation\Validator as BaseValidator;
use Override;

use function in_array;
use function is_null;
use function is_string;
use function trim;

class Validator extends BaseValidator
{
    protected const array NUMERIC_STRICT_TYPES = ['int', 'integer', 'float', 'double'];

    #[Override]
    protected function presentOrRuleIsImplicit($rule, $attribute, $value): bool
    {
        if ((is_null($value) || (is_string($value) && trim($value) === '')) && ! $this->hasRule($attribute, ['Nullable', 'Present', 'Sometimes'])) {
            return $this->isImplicit($rule);
        }

        return $this->validatePresent($attribute, $value)
               || $this->isImplicit($rule);
    }

    #[Override]
    protected function getSize($attribute, $value): int|string
    {
        $this->treatStrictNumericTypeAsNumericRule($attribute);

        return parent::getSize($attribute, $value);
    }

    #[Override]
    protected function getAttributeType($attribute): string
    {
        $this->treatStrictNumericTypeAsNumericRule($attribute);

        return parent::getAttributeType($attribute);
    }

    private function treatStrictNumericTypeAsNumericRule(int|string $attribute): void
    {
        if (in_array('Strict', $this->numericRules, true)) {
            return;
        }

        $rule = $this->getRule($attribute, ['Strict']);

        if ($rule === null) {
            return;
        }

        if (! in_array($rule[1][0] ?? null, static::NUMERIC_STRICT_TYPES, true)) {
            return;
        }

        $this->numericRules[] = 'Strict';
    }
}
