<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Elasticsearch;

use Elasticsearch\Client;
use InvalidArgumentException;

use function array_keys;
use function implode;
use function reset;

class ElasticsearchManager implements ElasticsearchManagerContract
{
    protected Client $client;
    protected bool $enabled;
    protected ?int $timeout = null;

    public function __construct(Client $client, bool $enabled = false)
    {
        $this->client = $client;
        $this->enabled = $enabled;
    }

    public function setEnabled(bool $enabled): ElasticsearchManagerContract
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function setTimeout(int $timeout): ElasticsearchManagerContract
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function createIndex(string $indexName, array $settings, array $mappings): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $params = [
            'index' => $indexName,
            'body'  => [
                'settings' => $settings,
                'mappings' => $mappings,
            ],
        ];

        if (! empty($this->timeout)) {
            $params['client'] = [
                'timeout' => $this->timeout,
            ];
        }

        $response = $this->client->indices()->create($params);

        if ($response['acknowledged'] !== true) {
            throw new ElasticsearchException('Something went wrong during index creation');
        }
    }

    public function deleteIndexes(array $names): void
    {
        if (! $this->isEnabled()) {
            return;
        }
        if (empty($names)) {
            throw new InvalidArgumentException('Index names can not be empty');
        }

        $params = [
            'index' => implode(',', $names),
        ];

        if (! empty($this->timeout)) {
            $params['client'] = [
                'timeout' => $this->timeout,
            ];
        }

        $response = $this->client->indices()->delete($params);
        if ($response['acknowledged'] !== true) {
            throw new ElasticsearchException('Something went wrong during index deletion');
        }
    }

    public function deleteIndexesByAlias(string $aliasName): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $params = [
            'name' => $aliasName,
        ];

        $response = $this->client->indices()->getAlias($params);
        if (empty($response)) {
            throw new ElasticsearchException('Can not get alias ' . $aliasName);
        }

        $indexes = array_keys($response);
        $this->deleteIndexes($indexes);
    }

    /**
     * @throws \Longman\LaravelLodash\Elasticsearch\ElasticsearchException
     */
    public function addDocumentsToIndex(string $indexName, string $typeName, array $items)
    {
        if (! $this->isEnabled()) {
            return;
        }

        $params = [
            'body' => [],
        ];

        if (! empty($this->timeout)) {
            $params['client'] = [
                'timeout' => $this->timeout,
            ];
        }

        foreach ($items as $id => $item) {
            $params['body'][] = [
                'create' => [
                    '_index' => $indexName,
                    '_type'  => $typeName,
                    '_id'    => $id,
                ],
            ];

            $params['body'][] = $item;
        }

        $responses = $this->client->bulk($params);
        if ($responses['errors'] !== true) {
            return;
        }

        $this->handleBulkError($responses, 'Error occurred during bulk create');
    }

    /**
     * @throws \Longman\LaravelLodash\Elasticsearch\ElasticsearchException
     */
    public function updateDocumentsInIndex(string $indexName, string $typeName, array $items)
    {
        if (! $this->isEnabled()) {
            return;
        }

        $params = [
            'body' => [],
        ];

        if (! empty($this->timeout)) {
            $params['client'] = [
                'timeout' => $this->timeout,
            ];
        }

        foreach ($items as $id => $item) {
            $params['body'][] = [
                'update' => [
                    '_index' => $indexName,
                    '_type'  => $typeName,
                    '_id'    => $id,
                ],
            ];

            $params['body'][] = ['doc' => $item];
        }

        $responses = $this->client->bulk($params);
        if ($responses['errors'] !== true) {
            return;
        }

        $this->handleBulkError($responses, 'Error occurred during bulk update');
    }

    /**
     * @throws \Longman\LaravelLodash\Elasticsearch\ElasticsearchException
     */
    public function addOrUpdateDocumentsInIndex(string $indexName, string $typeName, array $items)
    {
        if (! $this->isEnabled()) {
            return;
        }

        $params = [
            'body' => [],
        ];

        if (! empty($this->timeout)) {
            $params['client'] = [
                'timeout' => $this->timeout,
            ];
        }

        foreach ($items as $id => $item) {
            $params['body'][] = [
                'index' => [
                    '_index' => $indexName,
                    '_type'  => $typeName,
                    '_id'    => $id,
                ],
            ];

            $params['body'][] = $item;
        }

        $responses = $this->client->bulk($params);
        if ($responses['errors'] !== true) {
            return;
        }

        $this->handleBulkError($responses, 'Error occurred during bulk index');
    }

    public function refreshIndex(string $indexName): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $params = [
            'index' => $indexName,
        ];

        if (! empty($this->timeout)) {
            $params['client'] = [
                'timeout' => $this->timeout,
            ];
        }

        $this->client->indices()->refresh($params);
    }

    public function performSearch(ElasticsearchQueryContract $query): array
    {
        if (! $this->isEnabled()) {
            return [];
        }

        $params = $query->build();
        if (! empty($this->timeout)) {
            $params['client'] = [
                'timeout' => $this->timeout,
            ];
        }

        $results = $this->client->search($params);

        return $results;
    }

    public function switchIndexAlias(string $aliasName, string $indexName): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $params = [
            'name' => $aliasName,
        ];

        $exists = $this->client->indices()->existsAlias($params);

        $actions = [];
        // If alias already exists remove from indexes
        if ($exists) {
            $params = [
                'name' => $aliasName,
            ];

            $response = $this->client->indices()->getAlias($params);
            if (empty($response)) {
                throw new ElasticsearchException('Can not get alias ' . $aliasName);
            }

            $indexes = array_keys($response);

            foreach ($indexes as $index) {
                $actions[] = [
                    'remove' => [
                        'index' => $index,
                        'alias' => $aliasName,
                    ],
                ];
            }
        }

        $actions[] = [
            'add' => [
                'index' => $indexName,
                'alias' => $aliasName,
            ],
        ];

        $params = [
            'body' => [
                'actions' => $actions,
            ],
        ];

        $response = $this->client->indices()->updateAliases($params);
        if ($response['acknowledged'] !== true) {
            throw new ElasticsearchException('Switching alias response error');
        }
    }

    public function createTemplate(string $name, array $settings): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $params = [
            'name' => $name,
            'body' => $settings,
        ];

        if (! empty($this->timeout)) {
            $params['client'] = [
                'timeout' => $this->timeout,
            ];
        }

        $response = $this->client->indices()->putTemplate($params);

        if ($response['acknowledged'] !== true) {
            throw new ElasticsearchException('Something went wrong during template creation');
        }
    }

    public function ping(): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        return $this->client->ping();
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @throws \Longman\LaravelLodash\Elasticsearch\ElasticsearchException
     */
    protected function handleBulkError(array $responses, string $message): void
    {
        $errors = [];
        foreach ($responses['items'] as $item) {
            $row = $item;
            $row = reset($row);
            if (empty($row['error'])) {
                continue;
            }

            $errors[] = $row['error'];
        }

        throw new ElasticsearchException($message, $errors);
    }
}
