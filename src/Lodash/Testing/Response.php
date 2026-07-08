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
use function array_merge;
use function json_decode;

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

    protected static function resolveExpectedId(Model $model): string
    {
        if ($model instanceof UuidAsPrimaryContract) {
            return $model->getUidString();
        }

        return (string) $model->getKey();
    }
}
