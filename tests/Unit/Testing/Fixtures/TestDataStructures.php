<?php

declare(strict_types=1);

namespace Tests\Unit\Testing\Fixtures;

use Longman\LaravelLodash\Testing\DataStructuresProvider;

/**
 * @method static array getUserStructure(array $relations = [])
 */
class TestDataStructures extends DataStructuresProvider
{
    protected static array $userStructure = [
        'id',
        'name',
    ];
    protected static array $programStructure = [
        'id',
        'title',
    ];
    protected static array $adminProgramStructure = [
        'id',
        'title',
        'code',
    ];
    protected static array $adminFacultyStructure = [
        'id',
        'facultyName',
    ];
    protected static array $rolesStructure = [
        'id',
        'role',
    ];
    protected static array $adminRoleStructure = [
        'id',
        'role',
        'scope',
    ];
    protected static array $publicRoleStructure = [
        'id',
        'role',
    ];
    protected static array $adminsStructure = [
        'id',
        'adminName',
    ];
    protected static array $itemStructure = [
        'id',
        'itemName',
    ];
    protected static array $adminStudentStructure = [
        'id',
        'type',
        'attributes' => [
            'name',
        ],
    ];
}
