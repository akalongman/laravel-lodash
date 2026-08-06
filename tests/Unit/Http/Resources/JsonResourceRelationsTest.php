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
}
