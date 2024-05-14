<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Http\Resources;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection as BaseResourceCollection;
use Longman\LaravelLodash\Http\Resources\Response\PaginatedResourceResponse;

use function array_merge_recursive;
use function is_null;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

class JsonResourceCollection extends BaseResourceCollection
{
    public function toArray($request): array
    {
        /** @var \Longman\LaravelLodash\Http\Resources\JsonResource $item */
        foreach ($this->collection as $item) {
            $item->setDataWrapper('');
        }

        return ['data' => $this->collection];
    }

    public function withRelations(array $relations = []): self
    {
        /** @var \Longman\LaravelLodash\Http\Resources\JsonResource $item */
        foreach ($this->collection as $item) {
            $item->withRelations($relations);
        }

        return $this;
    }

    public function appendAdditional(array $data): self
    {
        $this->additional = array_merge_recursive($this->additional, $data);

        return $this;
    }

    public function jsonOptions(): int
    {
        return JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
    }

    protected function preparePaginatedResponse($request)
    {
        if ($this->preserveAllQueryParameters) {
            $this->resource->appends($request->query());
        } elseif (! is_null($this->queryParameters)) {
            $this->resource->appends($this->queryParameters);
        }

        return (new PaginatedResourceResponse($this))->toResponse($request);
    }
}
