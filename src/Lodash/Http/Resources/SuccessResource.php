<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Http\Resources;

use function array_merge;

class SuccessResource extends JsonResource
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
         * Merge additional info and unset it
         */
        $result = array_merge(
            $this->resource,
            $this->additional ?? [],
        );

        $this->additional([]);

        return $result;
    }
}
