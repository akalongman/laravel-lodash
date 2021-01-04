<?php

declare(strict_types=1);

namespace Tests\Unit\Testing;

use Longman\LaravelLodash\Testing\Attributes;
use Tests\Unit\TestCase;

class AttributesTest extends TestCase
{
    /** @test */
    public function it_should_parse_attributes(): void
    {
        $attributes = [
            'name'                 => 'User Name',
            'name2'                => 'User Name 2',
            'relations:choices:5'  => [
                'choice_name'        => 'Choice Name',
                'choice_name2'       => 'Choice Name 2',
                'relations:groups:1' => [
                    'group_name'            => 'Group Name',
                    'group_name2'           => 'Group Name 2',
                    'relations:lecturers:2' => [
                        'lecturer_name'        => 'Lecturer Name',
                        'lecturer_name2'       => 'Lecturer Name 2',
                        'relations:settings:1' => [
                            'settings_name'  => 'Settings Name',
                            'settings_name2' => 'Settings Name 2',
                        ],
                    ],
                ],
            ],
            'relations:profiles:3' => [
                'profile_name'         => 'Profile Name',
                'profile_name2'        => 'Profile Name 2',
                'relations:settings:1' => [
                    'settings_name'  => 'Settings Name',
                    'settings_name2' => 'Settings Name 2',
                ],
            ],
        ];

        $parser = new Attributes($attributes);

        $this->assertEquals(
            [
                'name'  => 'User Name',
                'name2' => 'User Name 2',
            ],
            $parser->getAttributes(),
        );
        $this->assertEquals(
            [
                'name'        => 'User Name',
                'name2'       => 'User Name 2',
                'custom_attr' => 'Custom Attr for Merging',
            ],
            $parser->getAttributes(['custom_attr' => 'Custom Attr for Merging']),
        );

        $this->assertEquals(false, $parser->hasRelation('missing_relation'));
        $this->assertEquals(true, $parser->hasRelation('choices'));

        // Choices
        $relation = $parser->getRelation('choices');
        $this->assertEquals(
            [
                'choice_name'  => 'Choice Name',
                'choice_name2' => 'Choice Name 2',
            ],
            $relation->getAttributes(),
        );
        $this->assertEquals(5, $relation->getCount());

        // Groups
        $relation = $relation->getRelation('groups');
        $this->assertEquals(
            [
                'group_name'  => 'Group Name',
                'group_name2' => 'Group Name 2',
            ],
            $relation->getAttributes(),
        );
        $this->assertEquals(1, $relation->getCount());

        // Lecturers
        $relation = $relation->getRelation('lecturers');
        $this->assertEquals(
            [
                'lecturer_name'  => 'Lecturer Name',
                'lecturer_name2' => 'Lecturer Name 2',
            ],
            $relation->getAttributes(),
        );
        $this->assertEquals(2, $relation->getCount());

        // Settings
        $relation = $relation->getRelation('settings');
        $this->assertEquals(
            [
                'settings_name'  => 'Settings Name',
                'settings_name2' => 'Settings Name 2',
            ],
            $relation->getAttributes(),
        );
        $this->assertEquals(1, $relation->getCount());

        // Profiles
        $relations2 = $parser->getRelations();
        $this->assertEquals(
            [
                'profile_name'  => 'Profile Name',
                'profile_name2' => 'Profile Name 2',
            ],
            $relations2['profiles']->getAttributes(),
        );
        $this->assertEquals(3, $relations2['profiles']->getCount());

        // Profiles
        $relations2 = $relations2['profiles']->getRelations();
        $this->assertEquals(
            [
                'settings_name'  => 'Settings Name',
                'settings_name2' => 'Settings Name 2',
            ],
            $relations2['settings']->getAttributes(),
        );
        $this->assertEquals(1, $relations2['settings']->getCount());
    }
}
