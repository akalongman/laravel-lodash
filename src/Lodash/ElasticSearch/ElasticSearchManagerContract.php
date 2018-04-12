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

    public function createIndex(string $index_name, array $settings, array $mappings);

    public function deleteIndexes(array $names);

    public function addDocumentsToIndex(string $index_name, string $type_name, array $items);

    public function updateDocumentsInIndex(string $index_name, string $type_name, array $items);

    public function deleteIndexesByAlias(string $alias_name);

    public function refreshIndex(string $index_name);

    public function performSearch(ElasticSearchQueryContract $query): array;

    public function switchIndexAlias(string $alias_name, string $index_name);

    public function createTemplate(string $name, array $settings);

    public function ping(): bool;

    public function getClient(): Client;

    public function isEnabled(): bool;
}
