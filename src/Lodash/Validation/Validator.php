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
}
