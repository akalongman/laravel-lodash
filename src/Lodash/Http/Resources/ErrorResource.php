<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Http\Resources;

use function array_merge;

class ErrorResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(array $resource)
    {
        $this->resource = $resource;

        $this->setDataWrapper('');
    }

    public function toArray($request): array
    {
        /**
         * Merge additional info and unset it, due to it causing
         * `errors` array to be wrapped in unnecessary `data` property
         */
        $result = array_merge(
            [
                'errors' => [
                    'general' => $this->resource,
                ],
            ],
            $this->additional ?? [],
        );

        $this->additional([]);

        return $result;
    }
}
