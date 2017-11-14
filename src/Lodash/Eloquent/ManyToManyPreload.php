<?php
/*
 * This file is part of the Laravel Lodash package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Longman\LaravelLodash\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 * @method $this limitPerGroupViaSubQuery(Builder $query, int $limit = 10)
 * @method $this limitPerGroupViaUnion(Builder $query, int $limit = 10, array $pivotColumns = [])
 */
trait ManyToManyPreload
{
    public function scopeLimitPerGroupViaSubQuery(Builder $query, int $limit = 10): Model
    {
        $table = $this->getTable();
        $queryKeyColumn = $query->getQuery()->wheres[0]['column'];
        $join = $query->getQuery()->joins;
        $newQuery = $this->newQueryWithoutScopes();
        $connection = $this->getConnection();

        // Initialize MySQL variables inline
        $newQuery->from($connection->raw('(select @num:=0, @group:=0) as `vars`, ' . $this->quoteColumn($table)));

        // If no columns already selected, let's select *
        if (! $query->getQuery()->columns) {
            $newQuery->select($table . '.*');
        }

        // Make sure column aliases are unique
        $groupAlias = $table . '_grp';
        $numAlias = $table . '_rn';

        // Apply mysql variables
        $newQuery->addSelect($connection->raw(
            "@num := if(@group = {$this->quoteColumn($queryKeyColumn)}, @num+1, 1) as `{$numAlias}`, @group := {$this->quoteColumn($queryKeyColumn)} as `{$groupAlias}`"
        ));

        // Make sure first order clause is the group order
        $newQuery->getQuery()->orders = (array) $query->getQuery()->orders;
        array_unshift($newQuery->getQuery()->orders, [
            'column'    => $queryKeyColumn,
            'direction' => 'asc',
        ]);

        if ($join) {
            $leftKey = explode('.', $queryKeyColumn)[1];
            $leftKeyColumn = "`{$table}`.`{$leftKey}`";
            $newQuery->addSelect($queryKeyColumn);
            $newQuery->mergeBindings($query->getQuery());
            $newQuery->getQuery()->joins = (array) $query->getQuery()->joins;
            $query->whereRaw("{$leftKeyColumn} = {$this->quoteColumn($queryKeyColumn)}");
        }

        $query->from($connection->raw("({$newQuery->toSql()}) as `{$table}`"))
            ->where($numAlias, '<=', $limit);

        return $this;
    }

    private function quoteColumn(string $column): string
    {

        return '`' . str_replace('.', '`.`', $column) . '`';
    }

    public function scopeLimitPerGroupViaUnion(Builder $query, int $limit = 10, array $pivotColumns = []): Model
    {
        $table = $this->getTable();
        $queryKeyColumn = $query->getQuery()->wheres[0]['column'];
        $joins = $query->getQuery()->joins;
        $connection = $this->getConnection();

        $queryKeyValues = $query->getQuery()->wheres[0]['values'];
        $pivotTable = explode('.', $queryKeyColumn)[0];

        $joinLeftColumn = $joins[0]->wheres[0]['first'];
        $joinRightColumn = $joins[0]->wheres[0]['second'];
        $joinOperator = $joins[0]->wheres[0]['operator'];

        foreach ($queryKeyValues as $value) {
            if (! isset($unionQuery1)) {
                $unionQuery1 = $connection->table($pivotTable)
                    ->select([$table . '.*'])
                    ->join($table, $joinLeftColumn, $joinOperator, $joinRightColumn)
                    ->where($queryKeyColumn, '=', $value)
                    ->limit($limit);
            } else {
                $select = [
                    $table . '.*',
                ];

                foreach ($pivotColumns as $pivotColumn) {
                    $select[] = $pivotTable . '.' . $pivotColumn . ' as pivot_' . $pivotColumn;
                }

                $unionQuery2 = $connection->table($pivotTable)
                    ->select($select)
                    ->join($table, $joinLeftColumn, $joinOperator, $joinRightColumn)
                    ->where($queryKeyColumn, '=', $value)
                    ->limit($limit);

                $unionQuery1->unionAll($unionQuery2);
            }
        }

        if (! isset($unionQuery1)) {
            throw new InvalidArgumentException('Union query does not found');
        }

        $query->setQuery($unionQuery1);

        return $this;
    }
}
