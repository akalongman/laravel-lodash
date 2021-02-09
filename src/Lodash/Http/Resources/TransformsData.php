<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Http\Resources;

use LogicException;
use Longman\LaravelLodash\Support\Str;

use function call_user_func_array;
use function get_class;
use function in_array;
use function is_array;
use function key;
use function method_exists;

trait TransformsData
{
    /**
     * Fields transform mapping.
     * In array key should be a column name from database,
     * value can be just name (if getter exists for that name, or array [fieldName => getterMethod]).
     * If static getterMethod is defined in the resource class, it will be called and as a first argument will be passed TransformableContract $model,
     * Otherwise, model's method will be used.
     */
    protected static array $transformMapping = [];

    public static function getTransformFields(): array
    {
        return static::$transformMapping;
    }

    public static function transformToApi(TransformableContract $model): array
    {
        $fields = static::getTransformFields();
        $hiddenProperties = $model->getHidden();
        $transformed = [];
        foreach ($fields as $internalField => $transformValue) {
            if (in_array($internalField, $hiddenProperties, true)) {
                continue;
            }

            [$key, $value] = self::parseKeyValue($internalField, $transformValue, $model);

            $transformed[$key] = $value;
        }

        return $transformed;
    }

    public static function transformToInternal(array $fields): array
    {
        $modelTransformedFields = [];
        foreach (self::getTransformFields() as $key => $transformField) {
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

    private static function parseKeyValue(string $internalField, $transformValue, TransformableContract $model): array
    {
        if (is_array($transformValue)) {
            $key = key($transformValue);
            $method = $transformValue[$key];
            if (method_exists(static::class, $method)) { // Check if getter exists in the resource class
                $value = call_user_func_array([static::class, $method], ['model' => $model]);
            } elseif (method_exists($model, $method)) { // Check if getter exists in the model class
                $value = $model->$method();
            } else {
                throw new LogicException('Method ' . $method . ' does not available not for resource ' . static::class . ', not for model ' . get_class($model));
            }
        } else {
            // Try to find getter for external field
            $method = 'get' . Str::snakeCaseToCamelCase($transformValue);
            if (method_exists($model, $method)) {
                $key = $transformValue;
                $value = $model->$method();
            } else {
                // Call getter for internal field
                $method = 'get' . Str::snakeCaseToCamelCase($internalField);
                if (! method_exists($model, $method)) {
                    throw new LogicException('Field ' . $internalField . ' getter (' . $method . ') does not available for model ' . get_class($model));
                }
                $key = $transformValue;
                $value = $model->$method();
            }
        }

        return [$key, $value];
    }
}
