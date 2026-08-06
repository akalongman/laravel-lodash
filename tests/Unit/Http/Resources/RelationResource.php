<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Resources;

use Longman\LaravelLodash\Http\Resources\JsonResource;

class RelationResource extends JsonResource
{
    protected static array $transformMapping = [
        'name' => 'name',
    ];

    /** @var array<string, \Longman\LaravelLodash\Http\Resources\JsonResource|null> */
    private array $includes = [];

    public function __construct(?User $resource)
    {
        $this->resource = $resource;
    }

    /**
     * @param array<string, \Longman\LaravelLodash\Http\Resources\JsonResource|null> $includes
     */
    public function withIncludes(array $includes): self
    {
        $this->includes = $includes;

        return $this;
    }

    public function includeAvatar(): ?JsonResource
    {
        return $this->includes['avatar'] ?? null;
    }

    public function includeProfile(): ?JsonResource
    {
        return $this->includes['profile'] ?? null;
    }

    public function includeAccounts(): ?JsonResource
    {
        return $this->includes['accounts'] ?? null;
    }

    public function includeDegree(): ?JsonResource
    {
        return $this->includes['degree'] ?? null;
    }

    public function includeFaculty(): ?JsonResource
    {
        return $this->includes['faculty'] ?? null;
    }
}
