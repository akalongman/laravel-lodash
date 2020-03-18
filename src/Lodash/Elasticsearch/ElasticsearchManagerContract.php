<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Elasticsearch;

use Elasticsearch\Client;

interface ElasticsearchManagerContract
{
    public function setEnabled(bool $enabled): ElasticsearchManagerContract;

    public function setTimeout(int $timeout): ElasticsearchManagerContract;

    public function createIndex(string $indexName, array $settings, array $mappings): void;

    public function deleteIndexes(array $names): void;

    public function addDocumentsToIndex(string $indexName, string $typeName, array $items);

    public function updateDocumentsInIndex(string $indexName, string $typeName, array $items);

    public function addOrUpdateDocumentsInIndex(string $indexName, string $typeName, array $items);

    public function deleteIndexesByAlias(string $aliasName): void;

    public function refreshIndex(string $indexName): void;

    public function performSearch(ElasticsearchQueryContract $query): array;

    public function switchIndexAlias(string $aliasName, string $indexName): void;

    public function createTemplate(string $name, array $settings): void;

    public function ping(): bool;

    public function getClient(): Client;

    public function isEnabled(): bool;
}
