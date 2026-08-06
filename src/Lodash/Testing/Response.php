<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Testing;

use Arr;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Testing\TestResponse;
use LogicException;
use Longman\LaravelLodash\Eloquent\UuidAsPrimaryContract;
use PHPUnit\Framework\Assert as PHPUnit;

use function __;
use function array_key_exists;
use function array_merge;
use function count;
use function is_array;
use function is_string;
use function json_decode;
use function str_ends_with;
use function substr;

class Response extends TestResponse
{
    protected static array $successResponseStructure = [];
    protected static array $errorResponseStructure = [];
    protected static array $pagerMetaStructure = [
        'total',
        'count',
        'perPage',
        'currentPage',
        'totalPages',
        'links',
    ];
    protected static array $cursorMetaStructure = [
        'count',
        'perPage',
        'cursor' => [
            'next',
            'previous',
        ],
        'links' => [
            'next',
            'previous',
        ],
    ];
    protected static ?string $dataStructuresProvider = null;

    public static function setSuccessResponseStructure(array $structure): void
    {
        self::$successResponseStructure = $structure;
    }

    public static function setErrorResponseStructure(array $structure): void
    {
        self::$errorResponseStructure = $structure;
    }

    public static function setDataStructuresProvider(?string $class): void
    {
        self::$dataStructuresProvider = $class;
    }

    public function assertJsonDataCount(int $count): self
    {
        $response = $this->getDecodedContent();

        PHPUnit::assertCount($count, $response['data'] ?? []);

        return $this;
    }

    public function assertJsonDataPagination(array $data): self
    {
        $response = $this->getDecodedContent();

        PHPUnit::assertEquals($data['currentPage'], $response['meta']['pagination']['currentPage']);
        PHPUnit::assertEquals($data['perPage'], $response['meta']['pagination']['perPage']);
        PHPUnit::assertEquals($data['count'], $response['meta']['pagination']['count']);
        PHPUnit::assertEquals($data['total'], $response['meta']['pagination']['total']);

        return $this;
    }

    public function assertJsonDataCursor(array $data): self
    {
        $response = $this->getDecodedContent();

        PHPUnit::assertEquals($data['count'], $response['meta']['pagination']['count']);
        PHPUnit::assertEquals($data['perPage'], $response['meta']['pagination']['perPage']);
        PHPUnit::assertEquals($data['cursor'], $response['meta']['pagination']['cursor']);
        PHPUnit::assertEquals($data['links'], $response['meta']['pagination']['links']);

        return $this;
    }

    public function assertJsonDataCollectionStructure(
        array $data,
        bool $includePagerMeta = true,
        bool $includeCursorMeta = false,
        bool $exact = false,
    ): self {
        $decoded = $this->getDecodedContent();

        PHPUnit::assertArrayHasKey('data', $decoded, 'Response does not contain a "data" key.');
        PHPUnit::assertNotEmpty($decoded['data'], 'Data collection is empty.');
        PHPUnit::assertIsList($decoded['data'], 'Data is not a list.');

        $struct = self::$successResponseStructure;
        $struct['data'] = ['*' => $data];

        $metaStructure = null;
        if ($includePagerMeta) {
            $metaStructure = self::$pagerMetaStructure;
        }

        if ($includeCursorMeta) {
            $metaStructure = self::$cursorMetaStructure;
        }

        if ($metaStructure !== null) {
            $struct['meta'] = [
                'pagination' => $metaStructure,
            ];
        }

        $this->assertJsonStructure($struct);

        if ($exact) {
            $this->assertExactJsonStructure(['*' => $data], $decoded['data']);

            if ($metaStructure !== null) {
                $this->assertExactJsonStructure($metaStructure, $decoded['meta']['pagination']);
            }
        }

        return $this;
    }

    public function assertJsonDataItemStructure(array $data, bool $exact = false): self
    {
        $struct = ['data' => $data];

        $this->assertJsonStructure($struct);

        if ($exact) {
            $this->assertExactJsonStructure($data, $this->getDecodedContent()['data']);
        }

        return $this;
    }

    public function assertJsonDataResource(
        string $structure,
        Model $model,
        array $relations = [],
        ?string $type = null,
        bool $exact = true,
    ): self {
        $resolved = self::resolveDataStructure($structure, $relations);
        $resolved = self::resolveNullableRelations($resolved, [$this->getDecodedContent()['data'] ?? null]);

        $this->assertJsonDataItemStructure($resolved, $exact);

        $item = $this->getDecodedContent()['data'];

        PHPUnit::assertSame($type ?? $structure, $item['type'] ?? null, 'Resource type does not match.');
        PHPUnit::assertSame(self::resolveExpectedId($model), $item['id'] ?? null, 'Resource id does not match.');

        return $this;
    }

    public function assertJsonDataResources(
        string $structure,
        iterable $models,
        array $relations = [],
        ?string $type = null,
        bool $ordered = false,
        bool $includePagerMeta = true,
        bool $includeCursorMeta = false,
        bool $exact = true,
    ): self {
        $expectedIds = [];
        foreach ($models as $model) {
            $expectedIds[] = self::resolveExpectedId($model);
        }

        if ($expectedIds === []) {
            return $this->assertJsonDataResourcesEmpty($includePagerMeta, $includeCursorMeta, $exact);
        }

        $resolved = self::resolveDataStructure($structure, $relations);
        $rows = $this->getDecodedContent()['data'] ?? [];
        $resolved = self::resolveNullableRelations($resolved, is_array($rows) ? $rows : []);

        $this->assertJsonDataCollectionStructure($resolved, $includePagerMeta, $includeCursorMeta, $exact);

        $expectedType = $type ?? $structure;
        $actualIds = [];
        foreach ($this->getDecodedContent()['data'] as $index => $row) {
            PHPUnit::assertSame($expectedType, $row['type'] ?? null, 'Resource type does not match at index ' . $index . '.');
            $actualIds[] = $row['id'] ?? null;
        }

        $ordered
            ? PHPUnit::assertSame($expectedIds, $actualIds, 'Resource ids do not match the expected sequence.')
            : PHPUnit::assertEqualsCanonicalizing($expectedIds, $actualIds, 'Resource ids do not match the expected set.');

        return $this;
    }

    public function assertJsonErrorStructure(?string $message = null, bool $includeMeta = false): self
    {
        $structure = self::$errorResponseStructure;
        if (! $includeMeta) {
            $structure = Arr::except($structure, 'meta');
        }
        $this->assertJsonStructure($structure);
        $this->assertJson(['status' => 'error']);
        if ($message) {
            $this->assertJson(['message' => $message]);
        }

        return $this;
    }

    public function assertJsonValidationErrorStructure(array $errors = [], bool $includeMeta = false): self
    {
        $structure = self::$errorResponseStructure;
        if (! $includeMeta) {
            $structure = Arr::except($structure, 'meta');
        }
        $structure = array_merge($structure, ['errors']);
        $this->assertJsonStructure($structure);
        $this->assertJson(['message' => __('validation.error'), 'status' => 'error']);
        if ($errors) {
            $this->assertJsonValidationErrors($errors);
        }

        return $this;
    }

    public function assertJsonSuccessStructure(?string $message = null, bool $includeMeta = false): self
    {
        $structure = self::$successResponseStructure;
        if (! $includeMeta) {
            $structure = Arr::except($structure, 'meta');
        }
        $this->assertJsonStructure($structure);
        $this->assertJson(['status' => 'ok']);
        if ($message) {
            $this->assertJson(['message' => $message]);
        }

        return $this;
    }

    public function getDecodedContent(): array
    {
        $content = $this->getContent();

        return json_decode($content, true);
    }

    protected function assertJsonDataResourcesEmpty(bool $includePagerMeta, bool $includeCursorMeta, bool $exact): self
    {
        $decoded = $this->getDecodedContent();

        PHPUnit::assertArrayHasKey('data', $decoded, 'Response does not contain a "data" key.');
        PHPUnit::assertSame([], $decoded['data'], 'Data collection is not empty.');

        $struct = self::$successResponseStructure;

        $metaStructure = null;
        if ($includePagerMeta) {
            $metaStructure = self::$pagerMetaStructure;
        }

        if ($includeCursorMeta) {
            $metaStructure = self::$cursorMetaStructure;
        }

        if ($metaStructure !== null) {
            $struct['meta'] = [
                'pagination' => $metaStructure,
            ];
        }

        if ($struct !== []) {
            $this->assertJsonStructure($struct);
        }

        if ($exact && $metaStructure !== null) {
            $this->assertExactJsonStructure($metaStructure, $decoded['meta']['pagination']);
        }

        return $this;
    }

    protected static function resolveDataStructure(string $structure, array $relations): array
    {
        if (self::$dataStructuresProvider === null) {
            throw new LogicException('Data structures provider is not set. Call Response::setDataStructuresProvider() first.');
        }

        $method = 'get' . $structure . 'Structure';

        return self::$dataStructuresProvider::$method($relations);
    }

    /**
     * Resolve the "?" markers emitted by DataStructuresProvider for nullable relations.
     *
     * A populated relationship renders at "relationships.<name>.data" while an empty one
     * renders as null at "relationships.<name>", so one declared structure cannot describe
     * both. Each marked node is rewritten against the actual payload: to a presence-only
     * leaf when the relationship is null, or to the declared subtree when it is populated.
     * The leaf form keeps exact mode intact, because it still contributes the key.
     *
     * @param array<int, mixed> $actualItems
     */
    protected static function resolveNullableRelations(array $structure, array $actualItems): array
    {
        if (! isset($structure['relationships']) || ! is_array($structure['relationships'])) {
            return $structure;
        }

        $resolved = [];

        foreach ($structure['relationships'] as $key => $node) {
            $isNullable = is_string($key) && str_ends_with($key, '?');
            $name = $isNullable ? substr($key, 0, -1) : $key;

            $values = self::collectRelationValues($actualItems, $name);

            if ($isNullable) {
                self::assertNullableRelationPresent($actualItems, $name);
            }

            if ($isNullable && $values !== [] && self::allNull($values)) {
                $resolved[] = $name;

                continue;
            }

            $resolved[$name] = is_array($node)
                ? self::resolveNullableRelationNode($node, $values)
                : $node;
        }

        $structure['relationships'] = $resolved;

        return $structure;
    }

    /**
     * @param array<int, mixed> $values
     */
    protected static function resolveNullableRelationNode(array $node, array $values): array
    {
        if (! isset($node['data']) || ! is_array($node['data'])) {
            return $node;
        }

        $dataValues = [];
        foreach ($values as $value) {
            if (! is_array($value) || ! array_key_exists('data', $value)) {
                continue;
            }

            $dataValues[] = $value['data'];
        }

        if (isset($node['data']['*']) && is_array($node['data']['*'])) {
            $rows = [];
            foreach ($dataValues as $list) {
                if (! is_array($list)) {
                    continue;
                }

                foreach ($list as $row) {
                    $rows[] = $row;
                }
            }

            $node['data']['*'] = self::resolveNullableRelations($node['data']['*'], $rows);

            return $node;
        }

        $node['data'] = self::resolveNullableRelations($node['data'], $dataValues);

        return $node;
    }

    /**
     * @param array<int, mixed> $actualItems
     *
     * @return array<int, mixed>
     */
    protected static function collectRelationValues(array $actualItems, string $name): array
    {
        $values = [];

        foreach ($actualItems as $item) {
            if (! self::hasRelationKey($item, $name)) {
                continue;
            }

            $values[] = $item['relationships'][$name];
        }

        return $values;
    }

    /**
     * @param array<int, mixed> $actualItems
     */
    protected static function assertNullableRelationPresent(array $actualItems, string $name): void
    {
        $isCollection = count($actualItems) > 1;

        foreach ($actualItems as $index => $item) {
            $message = 'Nullable relationship "' . $name . '" is missing from the response'
                . ($isCollection ? ' at index ' . $index : '') . '.';

            PHPUnit::assertTrue(self::hasRelationKey($item, $name), $message);
        }
    }

    protected static function hasRelationKey(mixed $item, string $name): bool
    {
        if (! is_array($item) || ! is_array($item['relationships'] ?? null)) {
            return false;
        }

        return array_key_exists($name, $item['relationships']);
    }

    /**
     * @param array<int, mixed> $values
     */
    protected static function allNull(array $values): bool
    {
        foreach ($values as $value) {
            if ($value !== null) {
                return false;
            }
        }

        return true;
    }

    protected static function resolveExpectedId(Model $model): string
    {
        if ($model instanceof UuidAsPrimaryContract) {
            return $model->getUidString();
        }

        return (string) $model->getKey();
    }
}
