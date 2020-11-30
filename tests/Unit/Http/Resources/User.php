<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Longman\LaravelLodash\Http\Resources\TransformableContract;

class User extends Model implements TransformableContract
{
    protected $guarded = [];

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMail(): string
    {
        return $this->mail;
    }

    public function getHomeAddress(): string
    {
        return $this->home_address;
    }

    public function getCalculatedField(): int
    {
        return 7;
    }
}
