<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Validation;

readonly class StrictTypesValidator
{
    protected const NATIVE_TYPE_INT = 'integer';
    protected const NATIVE_TYPE_FLOAT = 'double';
    protected const NATIVE_TYPE_BOOL = 'boolean';

    protected const TYPE_MAP = [
        'int'     => self::NATIVE_TYPE_INT,
        'integer' => self::NATIVE_TYPE_INT,
        'float'   => self::NATIVE_TYPE_FLOAT,
        'double'  => self::NATIVE_TYPE_FLOAT,
        'bool'    => self::NATIVE_TYPE_BOOL,
        'boolean' => self::NATIVE_TYPE_BOOL,
    ];

    public function validate($attribute, $value, $parameters): bool
    {
        if (empty($parameters)) {
            return false;
        }

        $valueType = gettype($value);
        $requiredType = $parameters[0];

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
        ]);
    }
}
