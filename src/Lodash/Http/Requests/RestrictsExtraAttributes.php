<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Http\Requests;

use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

use function __;
use function app;
use function array_diff;
use function array_keys;
use function config;
use function method_exists;
use function preg_replace;

/**
 * @mixin \Illuminate\Foundation\Http\FormRequest
 */
trait RestrictsExtraAttributes
{
    protected function prepareForValidation(): void
    {
        $this->checkForNotAllowedProperties();

        parent::prepareForValidation();
    }

    private function checkForNotAllowedProperties(): void
    {
        $extraAttributes = array_diff(
            $this->getValidationData(),
            $this->getAllowedAttributes(),
        );

        if (! empty($extraAttributes)) {
            $messages = [];
            foreach ($extraAttributes as $attribute) {
                $message = __('validation.restrict_extra_attributes', ['attribute' => $attribute]);
                if (config('app.debug')) {
                    $message .= ' Request Class: ' . static::class;
                }

                $messages[] = $message;
            }

            throw ValidationException::withMessages($messages);
        }
    }

    private function getValidationData(): array
    {
        $data = Arr::dot($this->validationData());

        $return = [];
        foreach ($data as $key => $value) {
            $key = preg_replace('#\.\d+#', '.*', $key);
            $return[] = $key;
        }

        return $return;
    }

    private function getAllowedAttributes(): array
    {
        if (method_exists($this, 'possibleAttributes')) {
            return $this->possibleAttributes();
        }

        $rules = app()->call([$this, 'rules']);

        return array_keys($rules);
    }
}
