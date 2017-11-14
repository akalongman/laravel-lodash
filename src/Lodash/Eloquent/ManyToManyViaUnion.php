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
 * @method $this limitPerGroupViaUnion(Builder $query, int $limit = 10, array $pivotColumns = [])
 */
trait ManyToManyViaUnion
{
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
