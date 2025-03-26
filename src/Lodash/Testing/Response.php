<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Testing;

use Arr;
use Illuminate\Testing\Assert;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Assert as PHPUnit;

use function __;
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

        PHPUnit::assertEquals($data['page'], $response['meta']['pagination']['currentPage']);
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

        $this->assertJsonStructure($struct);

        return $this;
    }

    public function assertJsonDataItemStructure(array $data): self
    {
        $struct = ['data' => $data];

        $this->assertJsonStructure($struct);

        return $this;
    }

    public function assertJsonErrorStructure(): self
    {
        $this->assertJsonStructure(self::$errorResponseStructure);

        return $this;
    }

    public function assertJsonSuccessStructure(string $message = 'ok'): self
    {
        $this->assertJsonStructure(self::$successResponseStructure);
        $this->assertJson(['status' => $message]);

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

        //$this->assertJsonErrorStructure();
        $this->assertJson(['status' => 'error', 'message' => __('app.item_not_found')]);

        return $this;
    }

    public function assertIsInvalidItem(): Response
    {
        Assert::assertTrue(
            $this->isInvalidData(),
            'Response status code [' . $this->getStatusCode() . '] is not a invalid data status code.',
        );

        return $this;
    }

    public function assertInvalidData(): Response
    {
        Assert::assertTrue(
            $this->isInvalidData(),
            'Response status code [' . $this->getStatusCode() . '] is not a invalid data status code.',
        );
        $this->assertJsonErrorStructure();

        return $this;
    }

    public function isInvalidData(): bool
    {
        return $this->getStatusCode() === 422;
    }

    public function assertIsError(): void
    {
        $this->assertJsonStructure(self::$errorResponseStructure);
        $this->assertJson(['status' => 'error']);
    }

    public function assertIsOk(string $message = 'ok', bool $includeMeta = false): void
    {
        $structure = self::$successResponseStructure;
        if (! $includeMeta) {
            $structure = Arr::except($structure, 'meta');
        }
        $this->assertJsonStructure($structure);
        $this->assertJson(['status' => $message]);
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
