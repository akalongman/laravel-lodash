<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Testing;

use Arr;
use Illuminate\Testing\TestResponse;
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

    public static function setSuccessResponseStructure(array $structure): void
    {
        self::$successResponseStructure = $structure;
    }

    public static function setErrorResponseStructure(array $structure): void
    {
        self::$errorResponseStructure = $structure;
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

    public function assertJsonDataCollectionStructure(array $data, bool $includePagerMeta = true): self
    {
        $struct = self::$successResponseStructure;
        $struct['data'] = [$data];

        if ($includePagerMeta) {
            $struct['meta'] = [
                'pagination' => self::$pagerMetaStructure,
            ];
        }

        PHPUnit::assertNotEmpty($this->getDecodedContent()['data'], 'Data collection is empty.');

        $this->assertJsonStructure($struct);

        return $this;
    }

    public function assertJsonDataItemStructure(array $data): self
    {
        $struct = ['data' => $data];

        $this->assertJsonStructure($struct);

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

    public function assertForbidden(): Response
    {
        parent::assertForbidden();

        //$this->assertJsonErrorStructure();
        //$this->assertJson(['message' => 'This action is unauthorized.']);

        return $this;
    }

    public function assertNotFound(): Response
    {
        parent::assertNotFound();

        $this->assertJson(['status' => 'error', 'message' => __('app.item_not_found')]);

        return $this;
    }

    public function assertOk(): Response
    {
        parent::assertOk();

        $this->assertJsonSuccessStructure();

        return $this;
    }

    public function assertCreated(): Response
    {
        parent::assertCreated();

        $this->assertJsonSuccessStructure();

        return $this;
    }
}
