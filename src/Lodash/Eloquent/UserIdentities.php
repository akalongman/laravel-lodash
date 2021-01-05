<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

use function auth;
use function class_exists;
use function class_uses_recursive;
use function get_class;
use function in_array;
use function is_null;

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
     * @param string $related
     * @param string $foreignKey
     * @param string $ownerKey
     * @param string $relation
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    abstract public function belongsTo($related, $foreignKey = null, $ownerKey = null, $relation = null);

    public static function usingSoftDeletes(): bool
    {
        static $usingSoftDeletes;

        if (is_null($usingSoftDeletes)) {
            return $usingSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive(static::class));
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

    protected static function bootUserIdentities(): void
    {
        // Creating
        static::creating(static function (Model $model) {
            if (! $model->usesUserIdentities()) {
                return;
            }

            if (is_null($model->{$model->columnCreatedBy})) {
                $model->{$model->columnCreatedBy} = auth()->id();
            }

            if (! is_null($model->{$model->columnUpdatedBy})) {
                return;
            }

            $model->{$model->columnUpdatedBy} = auth()->id();
        });

        // Updating
        static::updating(static function (Model $model) {
            if (! $model->usesUserIdentities()) {
                return;
            }

            $model->{$model->columnUpdatedBy} = auth()->id();
        });

        // Deleting/Restoring
        if (! static::usingSoftDeletes()) {
            return;
        }

        static::deleting(static function (Model $model) {
            if (! $model->usesUserIdentities()) {
                return;
            }

            if (is_null($model->{$model->columnDeletedBy})) {
                $model->{$model->columnDeletedBy} = auth()->id();
            }

            $model->save();
        });

        static::restoring(static function (Model $model) {
            if (! $model->usesUserIdentities()) {
                return;
            }

            $model->{$model->columnDeletedBy} = null;
        });
    }

    protected function getUserClass(): string
    {
        $provider = auth()->guard()->getProvider();
        if ($provider) {
            return $provider->getModel();
        }

        $user = auth()->guard()->user();
        if ($user) {
            return get_class($user);
        }

        if (class_exists(\App\Models\User::class)) {
            return \App\Models\User::class;
        }

        throw new InvalidArgumentException('User class can not detected');
    }
}
