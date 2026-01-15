<?php

declare(strict_types=1);

namespace Longman\LaravelLodash\Testing\Concerns;

use Longman\LaravelLodash\Testing\Constraints\CountWithWhereInDatabase;

/** @mixin \Illuminate\Foundation\Testing\TestCase */
trait InteractsWithDatabase
{
    protected function assertDatabaseCountWithWhere(string $table, int $count, array $where, $connection = null): static
    {
        $this->assertThat(
            $this->getTable($table),
            new CountWithWhereInDatabase($this->getConnection($connection, $table), $count, $where),
        );

        return $this;
    }
}
