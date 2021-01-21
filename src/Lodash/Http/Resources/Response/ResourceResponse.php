<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Http\Resources\Response;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceResponse as BaseResourceResponse;

use function response;
use function tap;

// phpcs:disable SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

// phpcs:enable

class ResourceResponse extends BaseResourceResponse
{
    public function toResponse($request): JsonResponse
    {
        $jsonOptions = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

        return tap(response()->json(
            $this->wrap(
                $this->resource->resolve($request),
                $this->resource->with($request),
                $this->resource->additional,
            ),
            $this->calculateStatus(),
            [],
            $jsonOptions,
        ), function ($response) use ($request) {
            $response->original = $this->resource->resource;

            $this->resource->withResponse($request, $response);
        });
    }
}
