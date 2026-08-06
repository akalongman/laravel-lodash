<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Longman\LaravelLodash\Http\Resources\JsonResource;
use Longman\LaravelLodash\Http\Resources\JsonResourceCollection;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\TestCase;
use Throwable;

use function app;
use function array_merge;
use function array_values;
use function count;
use function implode;
use function json_decode;

class JsonResourceRelationsTest extends TestCase
{
    #[Test]
    public function it_should_render_a_requested_empty_relationship_as_null(): void
    {
        $decoded = $this->render(new RelationResource($this->makeUser()), ['avatar']);

        $this->assertArrayHasKey('relationships', $decoded['data']);
        $this->assertArrayHasKey('avatar', $decoded['data']['relationships']);
        $this->assertNull($decoded['data']['relationships']['avatar']);
    }

    #[Test]
    public function it_should_render_a_populated_relationship_under_the_data_wrapper(): void
    {
        $resource = (new RelationResource($this->makeUser()))->withIncludes([
            'avatar' => new RelationResource($this->makeUser(2)),
        ]);

        $decoded = $this->render($resource, ['avatar']);

        $this->assertArrayHasKey('data', $decoded['data']['relationships']['avatar']);
        $this->assertSame('2', $decoded['data']['relationships']['avatar']['data']['id']);
    }

    #[Test]
    public function it_should_render_both_empty_idioms_identically(): void
    {
        $viaNull = new RelationResource($this->makeUser());
        $viaNullWrappingResource = (new RelationResource($this->makeUser()))->withIncludes([
            'avatar' => new RelationResource(null),
        ]);

        $this->assertSame(
            $this->content($viaNull, ['avatar']),
            $this->content($viaNullWrappingResource, ['avatar']),
        );
    }

    #[Test]
    public function it_should_emit_the_relationships_block_when_every_relation_is_empty(): void
    {
        $decoded = $this->render(new RelationResource($this->makeUser()), ['avatar', 'profile']);

        $this->assertArrayHasKey('relationships', $decoded['data']);
        $this->assertArrayHasKey('avatar', $decoded['data']['relationships']);
        $this->assertArrayHasKey('profile', $decoded['data']['relationships']);
        $this->assertNull($decoded['data']['relationships']['avatar']);
        $this->assertNull($decoded['data']['relationships']['profile']);
    }

    #[Test]
    public function it_should_not_emit_relationships_when_no_includes_requested(): void
    {
        $decoded = $this->render(new RelationResource($this->makeUser()), []);

        $this->assertArrayNotHasKey('relationships', $decoded['data']);
    }

    #[Test]
    public function it_should_only_emit_requested_relationships(): void
    {
        $decoded = $this->render(new RelationResource($this->makeUser()), ['avatar']);

        $this->assertArrayHasKey('avatar', $decoded['data']['relationships']);
        $this->assertArrayNotHasKey('profile', $decoded['data']['relationships']);
    }

    #[Test]
    public function it_should_drop_a_nested_chain_under_an_empty_parent(): void
    {
        $decoded = $this->render(new RelationResource($this->makeUser()), ['accounts.degree']);

        $this->assertArrayHasKey('accounts', $decoded['data']['relationships']);
        $this->assertNull($decoded['data']['relationships']['accounts']);
        $this->assertArrayNotHasKey('degree', $decoded['data']['relationships']);
    }

    #[Test]
    public function it_should_render_a_populated_nested_chain(): void
    {
        $resource = (new RelationResource($this->makeUser()))->withIncludes([
            'accounts' => (new RelationResource($this->makeUser(2)))->withIncludes([
                'degree' => new RelationResource($this->makeUser(3)),
            ]),
        ]);

        $decoded = $this->render($resource, ['accounts.degree']);

        $accounts = $decoded['data']['relationships']['accounts']['data'];

        $this->assertSame('2', $accounts['id']);
        $this->assertSame('3', $accounts['relationships']['degree']['data']['id']);
    }

    #[Test]
    public function it_should_throw_when_the_include_method_does_not_exist(): void
    {
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Relation method does not exist: includeMissing');

        $this->render(new RelationResource($this->makeUser()), ['missing']);
    }

    #[Test]
    public function it_should_not_emit_relationships_when_the_underlying_resource_is_null(): void
    {
        $decoded = $this->render(new RelationResource(null), ['avatar']);

        $this->assertArrayNotHasKey('relationships', $decoded['data']);
    }

    #[Test]
    public function it_should_render_an_empty_relationship_on_every_collection_row(): void
    {
        $models = new Collection([
            $this->makeUser(1),
            $this->makeUser(2),
            $this->makeUser(3),
        ]);

        $collection = (new JsonResourceCollection($models, RelationResource::class))
            ->withRelations(['avatar']);

        $decoded = json_decode($collection->toResponse(app(Request::class))->content(), true);

        $this->assertCount(3, $decoded['data']);

        foreach ($decoded['data'] as $row) {
            $this->assertArrayHasKey('avatar', $row['relationships']);
            $this->assertNull($row['relationships']['avatar']);
        }
    }

    #[Test]
    public function it_should_render_the_same_payload_for_every_include_order(): void
    {
        $baseline = null;
        $baselineOrder = '';

        foreach (self::permutations(['accounts', 'accounts.degree', 'avatar']) as $order) {
            $decoded = $this->render($this->deeplyPopulatedResource(), $order);

            if ($baseline === null) {
                $baseline = $decoded;
                $baselineOrder = implode(',', $order);

                continue;
            }

            $this->assertEquals(
                $baseline,
                $decoded,
                'Include order [' . implode(',', $order) . '] rendered differently from [' . $baselineOrder . ']',
            );
        }
    }

    #[Test]
    public function it_should_keep_a_nested_include_when_its_ancestor_is_listed_after_it(): void
    {
        $decoded = $this->render($this->deeplyPopulatedResource(), ['accounts.degree', 'accounts']);

        $accounts = $decoded['data']['relationships']['accounts']['data'];

        $this->assertSame('2', $accounts['id']);
        $this->assertArrayHasKey('relationships', $accounts, 'The nested include was dropped by its ancestor.');
        $this->assertSame('3', $accounts['relationships']['degree']['data']['id']);
    }

    #[Test]
    public function it_should_keep_a_nested_include_when_defaults_precede_a_repeated_client_include(): void
    {
        $decoded = $this->render($this->deeplyPopulatedResource(), ['accounts', 'accounts.degree', 'accounts']);

        $accounts = $decoded['data']['relationships']['accounts']['data'];

        $this->assertArrayHasKey('relationships', $accounts, 'The nested include was dropped by its ancestor.');
        $this->assertSame('3', $accounts['relationships']['degree']['data']['id']);
    }

    #[Test]
    public function it_should_keep_a_three_level_chain_when_its_ancestor_is_listed_after_it(): void
    {
        $decoded = $this->render($this->deeplyPopulatedResource(), ['accounts.degree.faculty', 'accounts.degree']);

        $accounts = $decoded['data']['relationships']['accounts']['data'];

        $this->assertArrayHasKey('relationships', $accounts, 'The degree level was dropped by its ancestor.');

        $degree = $accounts['relationships']['degree']['data'];

        $this->assertSame('3', $degree['id']);
        $this->assertArrayHasKey('relationships', $degree, 'The faculty level was dropped by its ancestor.');
        $this->assertSame('4', $degree['relationships']['faculty']['data']['id']);
    }

    #[Test]
    public function it_should_merge_sibling_branches_under_a_shared_parent(): void
    {
        $decoded = $this->render($this->deeplyPopulatedResource(), ['accounts.degree', 'accounts.avatar']);

        $relationships = $decoded['data']['relationships']['accounts']['data']['relationships'];

        $this->assertSame('3', $relationships['degree']['data']['id']);
        $this->assertSame('5', $relationships['avatar']['data']['id']);
    }

    #[Test]
    public function it_should_render_a_repeated_include_exactly_once(): void
    {
        $this->assertSame(
            $this->content($this->deeplyPopulatedResource(), ['avatar']),
            $this->content($this->deeplyPopulatedResource(), ['avatar', 'avatar']),
        );
    }

    #[Test]
    public function it_should_throw_for_an_unknown_include_alongside_a_preserved_ancestor(): void
    {
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Relation method does not exist: includeMissing');

        $this->render($this->deeplyPopulatedResource(), ['accounts.degree', 'accounts', 'missing']);
    }

    private function makeUser(int $id = 1): User
    {
        return new User([
            'id'           => $id,
            'name'         => 'Custom Name ' . $id,
            'mail'         => 'Custom Mail ' . $id,
            'home_address' => 'Custom Address ' . $id,
        ]);
    }

    /**
     * @param array<int, string> $relations
     */
    private function render(JsonResource $resource, array $relations): array
    {
        return json_decode($this->content($resource, $relations), true);
    }

    /**
     * @param array<int, string> $relations
     */
    private function content(JsonResource $resource, array $relations): string
    {
        return $resource->withRelations($relations)->toResponse(app(Request::class))->content();
    }

    /**
     * A fresh instance per call: rendering merges the requested relations into the
     * resource, so reusing one instance across orderings would accumulate them.
     */
    private function deeplyPopulatedResource(): RelationResource
    {
        return (new RelationResource($this->makeUser()))->withIncludes([
            'accounts' => (new RelationResource($this->makeUser(2)))->withIncludes([
                'degree' => (new RelationResource($this->makeUser(3)))->withIncludes([
                    'faculty' => new RelationResource($this->makeUser(4)),
                ]),
                'avatar' => new RelationResource($this->makeUser(5)),
            ]),
            'avatar'   => new RelationResource($this->makeUser(6)),
        ]);
    }

    /**
     * @param array<int, string> $items
     * @return array<int, array<int, string>>
     */
    private static function permutations(array $items): array
    {
        if (count($items) <= 1) {
            return [$items];
        }

        $result = [];
        foreach ($items as $index => $item) {
            $remaining = $items;
            unset($remaining[$index]);

            foreach (self::permutations(array_values($remaining)) as $permutation) {
                $result[] = array_merge([$item], $permutation);
            }
        }

        return $result;
    }
}
