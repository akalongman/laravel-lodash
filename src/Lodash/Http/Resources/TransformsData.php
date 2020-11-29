<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Http\Resources;

use LogicException;
use Longman\LaravelLodash\Support\Str;

use function get_class;
use function in_array;
use function is_array;
use function key;
use function method_exists;

trait TransformsData
{
    /**
     * Fields transform mapping.
     * In array key should be a fields name from database,
     * value can be just name (if getter exists for that name, or array [fieldName => getterMethod])
     */
    protected array $transformMapping = [];

    public function getTransformFields(): array
    {
        return $this->transformMapping;
    }

    public function transformToApi(TransformableContract $model): array
    {
        $fields = $this->getTransformFields();
        $hiddenProperties = $model->getHidden();
        $transformed = [];
        foreach ($fields as $internalField => $transformValue) {
            if (in_array($internalField, $hiddenProperties, true)) {
                continue;
            }

            [$key, $value] = $this->parseKeyValue($internalField, $transformValue, $model);

            $transformed[$key] = $value;
        }

        return $transformed;
    }

    public function transformToInternal(array $fields): array
    {
        $modelTransformedFields = [];
        foreach ($this->getTransformFields() as $key => $transformField) {
            if (is_array($transformField)) {
                $modelTransformedFields[key($transformField)] = $key;
            } else {
                $modelTransformedFields[$transformField] = $key;
            }
        }

        $transformed = [];
        foreach ($fields as $fieldKey => $postValue) {
            if (isset($modelTransformedFields[$fieldKey])) {
                $transformed[$modelTransformedFields[$fieldKey]] = $postValue;
            }
        }

        return $transformed;
    }

    private function parseKeyValue(string $internalField, $transformValue, TransformableContract $model): array
    {
        if (is_array($transformValue)) {
            $key = key($transformValue);
            $value = $model->{$transformValue[$key]}();
        } else {
            // Try to find getter for external field
            $method = 'get' . Str::snakeCaseToCamelCase($transformValue);
            if (method_exists($model, $method)) {
                $key = $transformValue;
                $value = $model->{$method}();
            } else {
                // Call getter for internal field
                $method = 'get' . Str::snakeCaseToCamelCase($internalField);
                if (! method_exists($model, $method)) {
                    throw new LogicException('Field ' . $internalField . ' getter (' . $method . ') does not available for model ' . get_class($model));
                }
                $key = $transformValue;
                $value = $model->{$method}();
            }
        }

        return [$key, $value];
    }
}
