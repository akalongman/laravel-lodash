<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Http\Resources;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection as BaseResourceCollection;
use Longman\LaravelLodash\Http\Resources\Response\PaginatedResourceResponse;

use function is_null;

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
