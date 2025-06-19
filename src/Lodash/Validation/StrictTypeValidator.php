<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Validation;

use function gettype;
use function in_array;

readonly class StrictTypeValidator
{
    protected const string NATIVE_TYPE_INT = 'integer';
    protected const string NATIVE_TYPE_FLOAT = 'double';
    protected const string NATIVE_TYPE_BOOL = 'boolean';

    protected const array TYPE_MAP = [
        'int'     => self::NATIVE_TYPE_INT,
        'integer' => self::NATIVE_TYPE_INT,
        'float'   => self::NATIVE_TYPE_FLOAT,
        'double'  => self::NATIVE_TYPE_FLOAT,
        'bool'    => self::NATIVE_TYPE_BOOL,
        'boolean' => self::NATIVE_TYPE_BOOL,
    ];

    public function validate(string $attribute, mixed $value, array $parameters): bool
    {
        if (empty($parameters)) {
            return false;
        }

        $valueType = gettype($value);
        $requiredType = (string) $parameters[0];

        if (empty(static::TYPE_MAP[$requiredType]) || $this->isNativeTypeString($requiredType)) {
            return $valueType === $requiredType;
        }

        return $valueType === static::TYPE_MAP[$requiredType];
    }

    protected function isNativeTypeString(string $type): bool
    {
        return in_array($type, [
            static::NATIVE_TYPE_INT,
            static::NATIVE_TYPE_FLOAT,
            static::NATIVE_TYPE_BOOL,
        ], true);
    }
}
