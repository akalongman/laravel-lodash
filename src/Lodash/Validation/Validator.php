<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Validation;

use Illuminate\Validation\Validator as BaseValidator;
use Override;

use function is_null;
use function is_string;
use function trim;

class Validator extends BaseValidator
{
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
        if (! $this->hasStrictNumericType($attribute)) {
            return parent::getSize($attribute, $value);
        }

        $originalNumericRules = $this->numericRules;
        $this->numericRules[] = 'Strict';

        try {
            return parent::getSize($attribute, $value);
        } finally {
            $this->numericRules = $originalNumericRules;
        }
    }

    #[Override]
    protected function getAttributeType($attribute): string
    {
        if (! $this->hasStrictNumericType($attribute)) {
            return parent::getAttributeType($attribute);
        }

        $originalNumericRules = $this->numericRules;
        $this->numericRules[] = 'Strict';

        try {
            return parent::getAttributeType($attribute);
        } finally {
            $this->numericRules = $originalNumericRules;
        }
    }

    private function hasStrictNumericType(int|string $attribute): bool
    {
        $rule = $this->getRule($attribute, ['Strict']);

        if ($rule === null) {
            return false;
        }

        return StrictTypeValidator::isNumericType((string) ($rule[1][0] ?? ''));
    }
}
