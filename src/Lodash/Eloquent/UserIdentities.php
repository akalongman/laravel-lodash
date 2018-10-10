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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 * @property \Illuminate\Contracts\Auth\Authenticatable $creator
 * @property \Illuminate\Contracts\Auth\Authenticatable $editor
 * @property \Illuminate\Contracts\Auth\Authenticatable $destroyer
 */
trait UserIdentities
{
    protected $userIdentities = true;
    protected $columnCreatedBy = 'created_by';
    protected $columnUpdatedBy = 'updated_by';
    protected $columnDeletedBy = 'deleted_by';

    /**
     * Define an inverse one-to-one or many relationship.
     *
     * @param  string $related
     * @param  string $foreignKey
     * @param  string $ownerKey
     * @param  string $relation
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public abstract function belongsTo($related, $foreignKey = null, $ownerKey = null, $relation = null);

    protected static function bootUserIdentities(): void
    {
        // Creating
        static::creating(function (Model $model) {
            if (! $model->usesUserIdentities()) {
                return;
            }

            if (is_null($model->{$model->columnCreatedBy})) {
                $model->{$model->columnCreatedBy} = auth()->id();
            }

            if (is_null($model->{$model->columnUpdatedBy})) {
                $model->{$model->columnUpdatedBy} = auth()->id();
            }
        });

        // Updating
        static::updating(function (Model $model) {
            if (! $model->usesUserIdentities()) {
                return;
            }

            $model->{$model->columnUpdatedBy} = auth()->id();
        });

        // Deleting/Restoring
        if (static::usingSoftDeletes()) {
            static::deleting(function (Model $model) {
                if (! $model->usesUserIdentities()) {
                    return;
                }

                if (is_null($model->{$model->columnDeletedBy})) {
                    $model->{$model->columnDeletedBy} = auth()->id();
                }

                $model->save();
            });

            static::restoring(function (Model $model) {
                if (! $model->usesUserIdentities()) {
                    return;
                }

                $model->{$model->columnDeletedBy} = null;
            });
        }
    }

    public static function usingSoftDeletes(): bool
    {
        static $usingSoftDeletes;

        if (is_null($usingSoftDeletes)) {
            return $usingSoftDeletes = in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(get_called_class()));
        }

        return $usingSoftDeletes;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo($this->getUserClass(), $this->columnCreatedBy);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo($this->getUserClass(), $this->columnUpdatedBy);
    }

    public function destroyer(): BelongsTo
    {
        return $this->belongsTo($this->getUserClass(), $this->columnDeletedBy);
    }

    public function usesUserIdentities(): bool
    {
        return $this->userIdentities;
    }

    public function stopUserIdentities(): self
    {
        $this->userIdentities = false;

        return $this;
    }

    public function startUserIdentities(): self
    {
        $this->userIdentities = true;

        return $this;
    }

    protected function getUserClass(): string
    {
        if (get_class(auth()) === \Illuminate\Auth\Guard::class) {
            return auth()->getProvider()->getModel();
        }

        return auth()->guard()->getProvider()->getModel();
    }
}
