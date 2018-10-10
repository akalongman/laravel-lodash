<?php
/*
 * This file is part of the Laravel Lodash package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Longman\LaravelLodash\ElasticSearch;

use ElasticSearch\Client;

interface ElasticSearchManagerContract
{
    public function setEnabled(bool $enabled): ElasticSearchManagerContract;

    public function setTimeout(int $timeout): ElasticSearchManagerContract;

    public function createIndex(string $index_name, array $settings, array $mappings): void;

    public function deleteIndexes(array $names): void;

    public function addDocumentsToIndex(string $index_name, string $type_name, array $items);

    public function updateDocumentsInIndex(string $index_name, string $type_name, array $items);

    public function addOrUpdateDocumentsInIndex(string $index_name, string $type_name, array $items);

    public function deleteIndexesByAlias(string $alias_name): void;

    public function refreshIndex(string $index_name): void;

    public function performSearch(ElasticSearchQueryContract $query): array;

    public function switchIndexAlias(string $alias_name, string $index_name): void;

    public function createTemplate(string $name, array $settings): void;

    public function ping(): bool;

    public function getClient(): Client;

    public function isEnabled(): bool;
}
