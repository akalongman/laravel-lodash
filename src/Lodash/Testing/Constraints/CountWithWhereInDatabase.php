<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Testing\Constraints;

use Illuminate\Database\Connection;
use PHPUnit\Framework\Constraint\Constraint;
use ReflectionClass;

use function sprintf;

class CountWithWhereInDatabase extends Constraint
{
    protected Connection $database;
    protected int $expectedCount;
    protected int $actualCount;
    protected array $where;

    public function __construct(Connection $database, int $expectedCount, array $where)
    {
        $this->expectedCount = $expectedCount;
        $this->database = $database;
        $this->where = $where;
    }

    public function toString(int $options = 0): string
    {
        return new ReflectionClass($this)->name;
    }

    protected function matches(mixed $table): bool
    {
        $this->actualCount = $this->database->table($table)->where($this->where)->count();

        return $this->actualCount === $this->expectedCount;
    }

    protected function failureDescription(mixed $table): string
    {
        return sprintf(
            "table [%s] matches expected entries count of %s with where. Entries found: %s.\n",
            $table,
            $this->expectedCount,
            $this->actualCount,
        );
    }
}
