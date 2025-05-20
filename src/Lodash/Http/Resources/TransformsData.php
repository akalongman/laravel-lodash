<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Http\Resources;

use LogicException;
use Longman\LaravelLodash\Support\Str;

use function array_replace;
use function call_user_func_array;
use function in_array;
use function is_array;
use function key;
use function method_exists;

trait TransformsData
{
    /**
     * Fields transform mapping.
     * In array key should be a column name from the database,
     * value can be just name (if getter exists for that name, or array [fieldName => getterMethod]).
     * If static getterMethod is defined in the resource class, it will be called and as a first argument will be passed TransformableContract $model,
     * Otherwise, the model's method will be used.
     */
    protected static array $transformMapping = [];

    /**
     * Fields list for merging with general mapping during transformation o internal. Used for updating some fields.
     * Array values should be a column name from the database.
     */
    protected static array $internalMapping = [];

    /**
     * Fields list for hiding in output.
     * Array values should be a column name from the database.
     */
    protected static array $hideInOutput = [];

    public static function getTransformFields(): array
    {
        return static::$transformMapping;
    }

    public static function getInternalFields(): array
    {
        return static::$internalMapping;
    }

    public static function getHideInOutput(): array
    {
        return static::$hideInOutput;
    }

    public static function transformToApi(TransformableContract $model): array
    {
        $fields = static::getTransformFields();
        $hiddenProperties = $model->getHidden();
        $hideInOutput = static::getHideInOutput();
        $transformed = [];
        foreach ($fields as $internalField => $transformValue) {
            if (in_array($internalField, $hiddenProperties, true)) {
                continue;
            }

            if (in_array($internalField, $hideInOutput, true)) {
                continue;
            }

            [$key, $value] = self::parseKeyValue($internalField, $transformValue, $model);

            $transformed[$key] = $value;
        }

        return $transformed;
    }

    public static function transformToInternal(array $fields): array
    {
        $transformFields = static::getTransformFields();
        $transformFields = array_replace($transformFields, static::getInternalFields());

        $modelTransformedFields = [];
        foreach ($transformFields as $key => $transformField) {
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
                throw new LogicException('Method ' . $method . ' does not available not for resource ' . static::class . ', not for model ' . $model::class);
            }
        } else {
            // Try to find getter for external field
            $method = 'get' . Str::snakeCaseToPascalCase($transformValue);
            if (method_exists($model, $method)) {
                $key = $transformValue;
                $value = $model->$method();
            } else {
                // Call getter for internal field
                $method = 'get' . Str::snakeCaseToPascalCase($internalField);
                if (! method_exists($model, $method)) {
                    throw new LogicException('Field ' . $internalField . ' getter (' . $method . ') does not available for model ' . $model::class);
                }
                $key = $transformValue;
                $value = $model->$method();
            }
        }

        return [$key, $value];
    }
}
