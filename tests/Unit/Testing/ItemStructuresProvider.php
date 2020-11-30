<?php

declare(strict_types=1);

namespace Tests\Unit\Testing;

use Longman\LaravelLodash\Testing\DataStructuresBuilder;

class ItemStructuresProvider
{
    // phpcs:disable LongishCodingStandard.NamingConventions.CamelCaseVariableName
    private array $user_structure = [
        'type',
        'id',
        'attributes' => [
            'uid',
            'firstName',
            'lastName',
            'fullName',
            'email',
            'avatar',
            'photoUrl',
        ],
    ];

    private array $user_profile_structure = [
        'type',
        'id',
        'attributes' => [
            'type',
            'degree',
        ],
    ];

    private array $profile_status_structure = [
        'type',
        'id',
        'attributes' => [
            'status',
            'message',
        ],
    ];

    // phpcs:enable

    public function getUserStructure(array $relations = []): array
    {
        $structure = $this->user_structure;

        DataStructuresBuilder::includeNestedRelations($this, $structure, $relations);

        return $structure;
    }

    public function getUserProfileStructure(array $relations = []): array
    {
        $structure = $this->user_profile_structure;

        DataStructuresBuilder::includeNestedRelations($this, $structure, $relations);

        return $structure;
    }

    public function getProfileStatusStructure(array $relations = []): array
    {
        $structure = $this->profile_status_structure;

        DataStructuresBuilder::includeNestedRelations($this, $structure, $relations);

        return $structure;
    }
}
