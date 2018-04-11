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
use InvalidArgumentException;
use RuntimeException;

class ElasticSearchManager implements ElasticSearchManagerContract
{
    /**
     * @var \ElasticSearch\Client
     */
    protected $client;

    /**
     * @var bool
     */
    protected $enabled;

    /**
     * @var int|null
     */
    protected $timeout;

    public function __construct(Client $client, bool $enabled = false)
    {
        $this->client = $client;
        $this->enabled = $enabled;
    }

    public function setEnabled(bool $enabled): ElasticSearchManagerContract
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function setTimeout(int $timeout): ElasticSearchManagerContract
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function createIndex(string $index_name, array $settings, array $mappings)
    {
        if (! $this->isEnabled()) {
            return;
        }

        $params = [
            'index' => $index_name,
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
            throw new RuntimeException('Something went wrong during index creation');
        }
    }

    public function deleteIndexes(array $names)
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
            throw new RuntimeException('Something went wrong during index deletion');
        }
    }

    public function deleteIndexesByAlias(string $alias_name)
    {
        if (! $this->isEnabled()) {
            return;
        }

        $params = [
            'name' => $alias_name,
        ];

        $response = $this->client->indices()->getAlias($params);
        if (empty($response)) {
            throw new RuntimeException('Can not get alias ' . $alias_name);
        }

        $indexes = array_keys($response);
        $this->deleteIndexes($indexes);
    }

    public function addDocumentsToIndex(string $index_name, string $type_name, array $items)
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
                    '_index' => $index_name,
                    '_type'  => $type_name,
                    '_id'    => $id,
                ],
            ];

            $params['body'][] = $item;
        }

        $responses = $this->client->bulk($params);
        if ($responses['errors'] === true) {
            throw new RuntimeException('Error occurred during bulk insert');
        }
    }

    public function refreshIndex(string $index_name)
    {
        if (! $this->isEnabled()) {
            return;
        }

        $params = [
            'index' => $index_name,
        ];

        if (! empty($this->timeout)) {
            $params['client'] = [
                'timeout' => $this->timeout,
            ];
        }

        $this->client->indices()->refresh($params);
    }

    public function performSearch(ElasticSearchQueryContract $query): array
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

    public function switchIndexAlias(string $alias_name, string $index_name)
    {
        if (! $this->isEnabled()) {
            return;
        }

        $params = [
            'name' => $alias_name,
        ];

        $exists = $this->client->indices()->existsAlias($params);

        $actions = [];
        // If alias already exists remove from indexes
        if ($exists) {
            $params = [
                'name' => $alias_name,
            ];

            $response = $this->client->indices()->getAlias($params);
            if (empty($response)) {
                throw new RuntimeException('Can not get alias ' . $alias_name);
            }

            $indexes = array_keys($response);

            foreach ($indexes as $index) {
                $actions[] = [
                    'remove' => [
                        'index' => $index,
                        'alias' => $alias_name,
                    ],
                ];
            }
        }

        $actions[] = [
            'add' => [
                'index' => $index_name,
                'alias' => $alias_name,
            ],
        ];

        $params = [
            'body' => [
                'actions' => $actions,
            ],
        ];

        $response = $this->client->indices()->updateAliases($params);
        if ($response['acknowledged'] !== true) {
            throw new RuntimeException('Switching alias response error');
        }
    }

    public function createTemplate(string $name, array $settings)
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
            throw new RuntimeException('Something went wrong during template creation');
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
}
