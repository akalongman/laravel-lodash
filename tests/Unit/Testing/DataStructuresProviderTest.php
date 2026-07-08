<?php

declare(strict_types=1);

namespace Tests\Unit\Testing;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\TestCase;
use Tests\Unit\Testing\Fixtures\TestDataStructures;

class DataStructuresProviderTest extends TestCase
{
    #[Test]
    public function it_should_resolve_dotted_chain_and_nested_array_identically(): void
    {
        $expected = [
            'id',
            'name',
            'relationships' => [
                'program' => [
                    'data' => [
                        'id',
                        'title',
                        'code',
                        'relationships' => [
                            'faculty' => [
                                'data' => [
                                    'id',
                                    'facultyName',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $dotted = TestDataStructures::getUserStructure(['program:AdminProgram.faculty:AdminFaculty']);
        $nested = TestDataStructures::getUserStructure(['program:AdminProgram' => ['faculty:AdminFaculty']]);

        $this->assertSame($expected, $dotted);
        $this->assertSame($expected, $nested);
    }

    #[Test]
    public function it_should_resolve_dots_inside_nested_children(): void
    {
        $dotted = TestDataStructures::getUserStructure(['program:AdminProgram.faculty:AdminFaculty.item']);
        $mixed = TestDataStructures::getUserStructure(['program:AdminProgram' => ['faculty:AdminFaculty.item']]);

        $this->assertSame($dotted, $mixed);
    }

    #[Test]
    public function it_should_nest_collections_at_every_level(): void
    {
        $expected = [
            'id',
            'name',
            'relationships' => [
                'roles' => [
                    'data' => [
                        '*' => [
                            'id',
                            'role',
                            'relationships' => [
                                'admins' => [
                                    'data' => [
                                        '*' => [
                                            'id',
                                            'adminName',
                                            'relationships' => [
                                                'item' => [
                                                    'data' => [
                                                        'id',
                                                        'itemName',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertSame($expected, TestDataStructures::getUserStructure(['roles[].admins[].item']));
    }

    #[Test]
    public function it_should_emit_collection_relations_under_the_wildcard_key(): void
    {
        $structure = TestDataStructures::getUserStructure(['roles[]']);

        $this->assertSame(['*' => ['id', 'role']], $structure['relationships']['roles']['data']);
    }

    #[Test]
    public function it_should_merge_overlapping_declarations_order_independently(): void
    {
        $forward = TestDataStructures::getUserStructure(['roles[]', 'roles[].admins[].item']);
        $reverse = TestDataStructures::getUserStructure(['roles[].admins[].item', 'roles[]']);

        $this->assertSame($forward, $reverse);
        $this->assertArrayHasKey('admins', $forward['relationships']['roles']['data']['*']['relationships']);
    }

    #[Test]
    public function it_should_prefer_explicit_structure_name_over_default(): void
    {
        $structure = TestDataStructures::getUserStructure(['roles[]', 'roles[]:AdminRole']);

        $this->assertSame(['*' => ['id', 'role', 'scope']], $structure['relationships']['roles']['data']);
    }

    #[Test]
    public function it_should_throw_on_conflicting_structure_names(): void
    {
        $this->expectException(InvalidArgumentException::class);

        TestDataStructures::getUserStructure(['roles[]:AdminRole', 'roles[]:PublicRole']);
    }

    #[Test]
    public function it_should_throw_on_inconsistent_collection_markers(): void
    {
        $this->expectException(InvalidArgumentException::class);

        TestDataStructures::getUserStructure(['roles', 'roles[].admins']);
    }

    #[Test]
    public function it_should_throw_on_legacy_bracket_syntax(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('roles[]');

        TestDataStructures::getUserStructure(['[roles]']);
    }

    #[Test]
    public function it_should_throw_on_misplaced_collection_marker(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('students[]:AdminStudent');

        TestDataStructures::getUserStructure(['students:AdminStudent[]']);
    }

    #[Test]
    public function it_should_throw_on_string_value_under_string_key(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('program:AdminProgram');

        TestDataStructures::getUserStructure(['program' => 'AdminProgram']);
    }

    #[Test]
    public function it_should_throw_on_unknown_structure_name(): void
    {
        $this->expectException(InvalidArgumentException::class);

        TestDataStructures::getUserStructure(['unknown']);
    }
}
