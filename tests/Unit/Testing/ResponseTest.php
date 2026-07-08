<?php

declare(strict_types=1);

namespace Tests\Unit\Testing;

use Illuminate\Http\JsonResponse;
use LogicException;
use Longman\LaravelLodash\Testing\Response;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\TestCase;
use Tests\Unit\Testing\Fixtures\TestDataStructures;
use Tests\Unit\Testing\Fixtures\TestUser;
use Tests\Unit\Testing\Fixtures\TestUuidUser;

class ResponseTest extends TestCase
{
    #[Test]
    public function it_should_pass_when_all_collection_rows_match_the_structure(): void
    {
        $response = $this->createResponse([
            'data' => [
                ['id' => 1, 'name' => 'One'],
                ['id' => 2, 'name' => 'Two'],
            ],
        ]);

        $response->assertJsonDataCollectionStructure(['id', 'name'], includePagerMeta: false);
    }

    #[Test]
    public function it_should_fail_when_a_later_collection_row_misses_a_key(): void
    {
        $response = $this->createResponse([
            'data' => [
                ['id' => 1, 'name' => 'One'],
                ['id' => 2],
            ],
        ]);

        $this->expectException(AssertionFailedError::class);

        $response->assertJsonDataCollectionStructure(['id', 'name'], includePagerMeta: false);
    }

    #[Test]
    public function it_should_fail_when_response_does_not_contain_data_key(): void
    {
        $response = $this->createResponse([
            'status' => 'ok',
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessageIsOrContains('Response does not contain a "data" key.');

        $response->assertJsonDataCollectionStructure(['id'], includePagerMeta: false);
    }

    #[Test]
    public function it_should_fail_when_data_collection_is_empty(): void
    {
        $response = $this->createResponse([
            'data' => [],
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessageIsOrContains('Data collection is empty.');

        $response->assertJsonDataCollectionStructure(['id'], includePagerMeta: false);
    }

    #[Test]
    public function it_should_fail_when_data_is_not_a_list(): void
    {
        $response = $this->createResponse([
            'data' => [
                'first' => ['id' => 1],
            ],
        ]);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessageIsOrContains('Data is not a list.');

        $response->assertJsonDataCollectionStructure(['id'], includePagerMeta: false);
    }

    #[Test]
    public function it_should_fail_when_a_row_contains_an_extra_key_in_exact_mode(): void
    {
        $response = $this->createResponse([
            'data' => [
                ['id' => 1, 'name' => 'One'],
                ['id' => 2, 'name' => 'Two', 'extra' => true],
            ],
        ]);

        $this->expectException(AssertionFailedError::class);

        $response->assertJsonDataCollectionStructure(['id', 'name'], includePagerMeta: false, exact: true);
    }

    #[Test]
    public function it_should_pass_when_a_row_contains_an_extra_key_in_loose_mode(): void
    {
        $response = $this->createResponse([
            'data' => [
                ['id' => 1, 'name' => 'One'],
                ['id' => 2, 'name' => 'Two', 'extra' => true],
            ],
        ]);

        $response->assertJsonDataCollectionStructure(['id', 'name'], includePagerMeta: false);
    }

    #[Test]
    public function it_should_fail_when_pagination_meta_contains_an_extra_key_in_exact_mode(): void
    {
        $response = $this->createResponse($this->paginatedPayload(['extra' => true]));

        $this->expectException(AssertionFailedError::class);

        $response->assertJsonDataCollectionStructure(['id', 'name'], exact: true);
    }

    #[Test]
    public function it_should_pass_when_pagination_meta_contains_an_extra_key_in_loose_mode(): void
    {
        $response = $this->createResponse($this->paginatedPayload(['extra' => true]));

        $response->assertJsonDataCollectionStructure(['id', 'name']);
    }

    #[Test]
    public function it_should_ignore_extra_envelope_keys_in_exact_mode(): void
    {
        Response::setSuccessResponseStructure(['status']);

        $response = $this->createResponse([
            'status' => 'ok',
            'debug'  => ['queries' => 3],
            'data'   => [
                ['id' => 1, 'name' => 'One'],
            ],
        ]);

        $response->assertJsonDataCollectionStructure(['id', 'name'], includePagerMeta: false, exact: true);
    }

    #[Test]
    public function it_should_fail_when_a_nested_block_contains_an_extra_key_in_exact_mode(): void
    {
        $response = $this->createResponse([
            'data' => [
                ['id' => 1, 'profile' => ['age' => 30, 'extra' => true]],
            ],
        ]);

        $this->expectException(AssertionFailedError::class);

        $response->assertJsonDataCollectionStructure(['id', 'profile' => ['age']], includePagerMeta: false, exact: true);
    }

    #[Test]
    public function it_should_fail_when_cursor_meta_contains_an_extra_key_in_exact_mode(): void
    {
        $response = $this->createResponse([
            'data' => [
                ['id' => 1, 'name' => 'One'],
            ],
            'meta' => [
                'pagination' => [
                    'count'   => 1,
                    'perPage' => 10,
                    'cursor'  => ['next' => null, 'previous' => null, 'extra' => true],
                    'links'   => ['next' => null, 'previous' => null],
                ],
            ],
        ]);

        $this->expectException(AssertionFailedError::class);

        $response->assertJsonDataCollectionStructure(
            ['id', 'name'],
            includePagerMeta: false,
            includeCursorMeta: true,
            exact: true,
        );
    }

    #[Test]
    public function it_should_fail_when_item_contains_an_extra_key_in_exact_mode(): void
    {
        $response = $this->createResponse([
            'data' => ['id' => 1, 'name' => 'One', 'extra' => true],
        ]);

        $this->expectException(AssertionFailedError::class);

        $response->assertJsonDataItemStructure(['id', 'name'], exact: true);
    }

    #[Test]
    public function it_should_fail_when_item_misses_a_key_in_exact_mode(): void
    {
        $response = $this->createResponse([
            'data' => ['id' => 1],
        ]);

        $this->expectException(AssertionFailedError::class);

        $response->assertJsonDataItemStructure(['id', 'name'], exact: true);
    }

    #[Test]
    public function it_should_pass_when_item_contains_an_extra_key_in_loose_mode(): void
    {
        $response = $this->createResponse([
            'data' => ['id' => 1, 'name' => 'One', 'extra' => true],
        ]);

        $response->assertJsonDataItemStructure(['id', 'name']);
    }

    #[Test]
    public function it_should_assert_multi_row_relationship_collections_from_provider_structures_in_exact_mode(): void
    {
        $structure = TestDataStructures::getUserStructure(['roles[]']);

        $response = $this->createResponse([
            'data' => [
                [
                    'id'            => 1,
                    'name'          => 'One',
                    'relationships' => [
                        'roles' => [
                            'data' => [
                                ['id' => 10, 'role' => 'admin'],
                                ['id' => 11, 'role' => 'editor'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertJsonDataCollectionStructure($structure, includePagerMeta: false, exact: true);
    }

    #[Test]
    public function it_should_assert_a_conforming_resource_item(): void
    {
        Response::setDataStructuresProvider(TestDataStructures::class);

        $response = $this->createResponse([
            'data' => ['id' => '5', 'type' => 'AdminStudent', 'attributes' => ['name' => 'Alice']],
        ]);

        $response->assertJsonDataResource('AdminStudent', new TestUser(['id' => 5]));
    }

    #[Test]
    public function it_should_fail_when_resource_id_does_not_match(): void
    {
        Response::setDataStructuresProvider(TestDataStructures::class);

        $response = $this->createResponse([
            'data' => ['id' => '5', 'type' => 'AdminStudent', 'attributes' => ['name' => 'Alice']],
        ]);

        $this->expectException(AssertionFailedError::class);

        $response->assertJsonDataResource('AdminStudent', new TestUser(['id' => 6]));
    }

    #[Test]
    public function it_should_fail_when_resource_type_does_not_match(): void
    {
        Response::setDataStructuresProvider(TestDataStructures::class);

        $response = $this->createResponse([
            'data' => ['id' => '5', 'type' => 'Other', 'attributes' => ['name' => 'Alice']],
        ]);

        $this->expectException(AssertionFailedError::class);

        $response->assertJsonDataResource('AdminStudent', new TestUser(['id' => 5]));
    }

    #[Test]
    public function it_should_fail_when_resource_has_an_extra_key_under_the_exact_default(): void
    {
        Response::setDataStructuresProvider(TestDataStructures::class);

        $response = $this->createResponse([
            'data' => ['id' => '5', 'type' => 'AdminStudent', 'attributes' => ['name' => 'Alice'], 'debug' => true],
        ]);

        $this->expectException(AssertionFailedError::class);

        $response->assertJsonDataResource('AdminStudent', new TestUser(['id' => 5]));
    }

    #[Test]
    public function it_should_respect_the_resource_type_override(): void
    {
        Response::setDataStructuresProvider(TestDataStructures::class);

        $response = $this->createResponse([
            'data' => ['id' => '5', 'type' => 'CustomType', 'attributes' => ['name' => 'Alice']],
        ]);

        $response->assertJsonDataResource('AdminStudent', new TestUser(['id' => 5]), type: 'CustomType');
    }

    #[Test]
    public function it_should_derive_the_expected_id_from_uuid_models(): void
    {
        Response::setDataStructuresProvider(TestDataStructures::class);

        $response = $this->createResponse([
            'data' => ['id' => 'abc-123', 'type' => 'AdminStudent', 'attributes' => ['name' => 'Alice']],
        ]);

        $response->assertJsonDataResource('AdminStudent', new TestUuidUser(['uid' => 'abc-123']));
    }

    #[Test]
    public function it_should_throw_when_no_data_structures_provider_is_registered(): void
    {
        $response = $this->createResponse([
            'data' => ['id' => '5', 'type' => 'AdminStudent', 'attributes' => ['name' => 'Alice']],
        ]);

        $this->expectException(LogicException::class);

        $response->assertJsonDataResource('AdminStudent', new TestUser(['id' => 5]));
    }

    #[Test]
    public function it_should_assert_collection_resources_by_id_set_regardless_of_order(): void
    {
        Response::setDataStructuresProvider(TestDataStructures::class);

        $response = $this->createResponse($this->resourceCollectionPayload());

        $response->assertJsonDataResources(
            'AdminStudent',
            [new TestUser(['id' => 1]), new TestUser(['id' => 2])],
            includePagerMeta: false,
        );
    }

    #[Test]
    public function it_should_fail_for_out_of_sequence_rows_when_ordered(): void
    {
        Response::setDataStructuresProvider(TestDataStructures::class);

        $response = $this->createResponse($this->resourceCollectionPayload());

        $this->expectException(AssertionFailedError::class);

        $response->assertJsonDataResources(
            'AdminStudent',
            [new TestUser(['id' => 1]), new TestUser(['id' => 2])],
            ordered: true,
            includePagerMeta: false,
        );
    }

    #[Test]
    public function it_should_pass_for_in_sequence_rows_when_ordered(): void
    {
        Response::setDataStructuresProvider(TestDataStructures::class);

        $response = $this->createResponse($this->resourceCollectionPayload());

        $response->assertJsonDataResources(
            'AdminStudent',
            [new TestUser(['id' => 2]), new TestUser(['id' => 1])],
            ordered: true,
            includePagerMeta: false,
        );
    }

    #[Test]
    public function it_should_fail_when_a_row_is_outside_the_expected_set(): void
    {
        Response::setDataStructuresProvider(TestDataStructures::class);

        $response = $this->createResponse($this->resourceCollectionPayload());

        $this->expectException(AssertionFailedError::class);

        $response->assertJsonDataResources(
            'AdminStudent',
            [new TestUser(['id' => 1]), new TestUser(['id' => 3])],
            includePagerMeta: false,
        );
    }

    #[Test]
    public function it_should_assert_the_type_on_every_collection_row(): void
    {
        Response::setDataStructuresProvider(TestDataStructures::class);

        $payload = $this->resourceCollectionPayload();
        $payload['data'][1]['type'] = 'Other';

        $response = $this->createResponse($payload);

        $this->expectException(AssertionFailedError::class);

        $response->assertJsonDataResources(
            'AdminStudent',
            [new TestUser(['id' => 1]), new TestUser(['id' => 2])],
            includePagerMeta: false,
        );
    }

    #[Test]
    public function it_should_pass_for_an_empty_expected_set_when_data_is_empty(): void
    {
        Response::setDataStructuresProvider(TestDataStructures::class);

        $response = $this->createResponse([
            'data' => [],
            'meta' => [
                'pagination' => [
                    'total'       => 0,
                    'count'       => 0,
                    'perPage'     => 10,
                    'currentPage' => 1,
                    'totalPages'  => 0,
                    'links'       => [],
                ],
            ],
        ]);

        $response->assertJsonDataResources('AdminStudent', []);
    }

    #[Test]
    public function it_should_fail_for_an_empty_expected_set_when_data_is_not_empty(): void
    {
        Response::setDataStructuresProvider(TestDataStructures::class);

        $response = $this->createResponse($this->resourceCollectionPayload());

        $this->expectException(AssertionFailedError::class);

        $response->assertJsonDataResources('AdminStudent', [], includePagerMeta: false);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Response::setSuccessResponseStructure([]);
        Response::setErrorResponseStructure([]);
        Response::setDataStructuresProvider(null);
    }

    protected function createResponse(array $payload): Response
    {
        return new Response(new JsonResponse($payload));
    }

    protected function resourceCollectionPayload(): array
    {
        return [
            'data' => [
                ['id' => '2', 'type' => 'AdminStudent', 'attributes' => ['name' => 'Bob']],
                ['id' => '1', 'type' => 'AdminStudent', 'attributes' => ['name' => 'Alice']],
            ],
        ];
    }

    protected function paginatedPayload(array $extraPaginationKeys = []): array
    {
        return [
            'data' => [
                ['id' => 1, 'name' => 'One'],
                ['id' => 2, 'name' => 'Two'],
            ],
            'meta' => [
                'pagination' => [
                    'total'       => 2,
                    'count'       => 2,
                    'perPage'     => 10,
                    'currentPage' => 1,
                    'totalPages'  => 1,
                    'links'       => [],
                ] + $extraPaginationKeys,
            ],
        ];
    }
}
