<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Http\Resources;

class TransformableArrayResource extends ArrayResource
{
    public function getTransformed(): array
    {
        $data = [];
        foreach ($this->getTransformFields() as $from => $to) {
            $data[$to] = $this->resource[$from];
        }

        return $data;
    }
}
