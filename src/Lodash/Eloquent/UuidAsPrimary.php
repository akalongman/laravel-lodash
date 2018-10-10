<?php
/*
 * This file is part of the Laravel Lodash package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Longman\LaravelLodash\Eloquent;

use Ramsey\Uuid\Uuid;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait UuidAsPrimary
{
    protected static function bootUuidAsPrimary(): void
    {
        /** @var \Illuminate\Database\Eloquent\Model $model */
        static::creating(function ($model) {
            $key_name = $model->getKeyName();

            if (empty($model->{$key_name})) {
                $uuidVersion = ! empty($model->uuidVersion) ? $model->uuidVersion : 4;

                $model->attributes[$key_name] = self::generateUuid($uuidVersion);
            }
        });
    }

    public static function generateUuid(int $version = 4): string
    {
        switch ($version) {
            default:
                $uuid = Uuid::uuid4()->toString();
                break;

            case 1:
                $uuid = Uuid::uuid1()->toString();
                break;

            case 3:
                $uuid = Uuid::uuid3()->toString();
                break;
        }

        return $uuid;
    }

    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }
}
