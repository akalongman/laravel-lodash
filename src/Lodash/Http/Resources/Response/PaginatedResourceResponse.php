<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Http\Resources\Response;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\PaginatedResourceResponse as BasePaginatedResourceResponse;
use Illuminate\Support\Arr;

use function array_merge_recursive;
use function count;
use function is_array;
use function response;
use function tap;

// phpcs:disable SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

// phpcs:enable

class PaginatedResourceResponse extends BasePaginatedResourceResponse
{
    public function toResponse($request): JsonResponse
    {
        $jsonOptions = JSON_UNESCAPED_SLASHES & JSON_UNESCAPED_UNICODE;

        return tap(response()->json(
            $this->wrap(
                $this->resource->resolve($request),
                array_merge_recursive(
                    $this->paginationInformation($request),
                    $this->resource->with($request),
                    $this->resource->additional,
                ),
            ),
            $this->calculateStatus(),
            [],
            $jsonOptions,
        ), function ($response) use ($request) {
            $response->original = $this->resource->resource->map(static function ($item) {
                return is_array($item) ? Arr::get($item, 'resource') : $item->resource;
            });

            $this->resource->withResponse($request, $response);
        });
    }

    protected function paginationInformation($request): array
    {
        $paginated = $this->resource->resource->toArray();

        return [
            'meta' => $this->meta($paginated),
        ];
    }

    protected function meta($paginated): array
    {
        return [
            'pagination' => [
                'total'       => $paginated['total'] ?? null,
                'count'       => count($paginated['data']) ?? null,
                'perPage'     => $paginated['per_page'],
                'currentPage' => $paginated['current_page'] ?? null,
                'totalPages'  => $paginated['last_page'] ?? null,
                'links'       => [
                    'next'     => $paginated['next_page_url'] ?? null,
                    'previous' => $paginated['prev_page_url'] ?? null,
                ],
            ],
        ];
    }
}
