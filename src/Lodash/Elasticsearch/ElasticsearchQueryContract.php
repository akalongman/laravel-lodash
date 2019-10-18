<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Elasticsearch;

interface ElasticsearchQueryContract
{
    public function build(): array;
}
