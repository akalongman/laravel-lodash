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
use function in_array;
use function method_exists;
use function preg_replace;

/**
 * @mixin \Illuminate\Foundation\Http\FormRequest
 */
trait RestrictsExtraAttributes
{
    protected bool $checkForExtraProperties = true;
    protected bool $checkForEmptyPayload = true;
    protected array $methodsForEmptyPayload = ['PATCH', 'POST', 'PUT'];

    protected function prepareForValidation(): void
    {
        $this->checkForNotAllowedProperties();

        $this->checkForEmptyPayload();

        parent::prepareForValidation();
    }

    private function checkForEmptyPayload(): void
    {
        if (! $this->checkForEmptyPayload) {
            return;
        }

        if (in_array($this->method(), $this->methodsForEmptyPayload, true)) {
            if (empty($this->input())) {
                throw ValidationException::withMessages(['general' => [__('validation.payload_is_empty')]]);
            }
        }
    }

    private function checkForNotAllowedProperties(): void
    {
        if (! $this->checkForExtraProperties) {
            return;
        }

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

                $messages[$attribute] = $message;
            }

            throw ValidationException::withMessages($messages);
        }
    }

    private function getValidationData(): array
    {
        $data = Arr::dot($this->validationData());

        $return = [];
        foreach ($data as $key => $value) {
            $key = preg_replace('#\.\d+#', '.*', (string) $key);
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
