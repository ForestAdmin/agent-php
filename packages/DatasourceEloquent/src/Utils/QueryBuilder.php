<?php

namespace ForestAdmin\AgentPHP\DatasourceEloquent\Utils;

use ForestAdmin\AgentPHP\BaseDatasource\Contracts\BaseCollectionContract;
use ForestAdmin\AgentPHP\BaseDatasource\Utils\QueryBuilder as BaseQueryBuilder;
use ForestAdmin\AgentPHP\DatasourceEloquent\EloquentCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\RelationSchema;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Str;

class QueryBuilder extends BaseQueryBuilder
{
    public function __construct(
        protected BaseCollectionContract $collection,
    ) {
        parent::__construct($collection);
        /** @var EloquentCollection $collection */
        $this->query = $collection->model->newQuery();
    }

    public function formatField(string $field): string
    {
        if (Str::contains($field, ':')) {
            $relation = $this->collection->getFields()[Str::before($field, ':')];
            $tableName = $this->collection
                ->getDataSource()
                ->getCollection($relation->getForeignCollection())->getTableName();
            $this->addJoinRelation($relation, $tableName);

            return $tableName . '.' . Str::after($field, ':');
        }

        return $this->tableName . '.' . $field;
    }

    //    protected function addJoinRelation(RelationSchema $relation, string $relationTableName): void
    //    {
    //        if ($relation instanceof ManyToManySchema) {
    //            $throughTable = $this->collection->getDataSource()->getCollection($relation->getThroughCollection())->getTableName();
    //            $joinTable = "$relationTableName as $relationTableName";
    //            $joinThroughTable = "$throughTable as $throughTable";
    //            if (! $this->isJoin($joinTable) && ! $this->isJoin($joinThroughTable)) {
    //                $this->query
    //                    ->leftJoin(
    //                        "$throughTable as $throughTable",
    //                        $this->tableName . '.' . $relation->getOriginKeyTarget(),
    //                        '=',
    //                        $throughTable . '.' . $relation->getOriginKey()
    //                    )
    //                    ->leftJoin(
    //                        "$relationTableName as $relationTableName",
    //                        $throughTable . '.' . $relation->getForeignKey(),
    //                        '=',
    //                        $relationTableName . '.' . $relation->getForeignKeyTarget()
    //                    );
    //            }
    //        } else {
    //            $joinTable = "$relationTableName as $relationTableName";
    //            if (
    //                ($relation instanceof OneToOneSchema || $relation instanceof OneToManySchema)
    //                && ! $this->isJoin($joinTable)
    //            ) {
    //                $this->query->leftJoin(
    //                    $joinTable,
    //                    $this->tableName . '.' . $relation->getOriginKey(),
    //                    '=',
    //                    $relationTableName . '.' . $relation->getOriginKeyTarget()
    //                );
    //            } elseif ($relation instanceof ManyToOneSchema && ! $this->isJoin($joinTable)) {
    //                $this->query->leftJoin(
    //                    $joinTable,
    //                    $this->tableName . '.' . $relation->getForeignKey(),
    //                    '=',
    //                    $relationTableName . '.' . $relation->getForeignKeyTarget()
    //                );
    //            }
    //        }
    //    }

    protected function isJoin(string $joinTable): bool
    {
        /** @var JoinClause $join */
        foreach ($this->query->getQuery()->joins ?? [] as $join) {
            if ($join->table === $joinTable) {
                return true;
            }
        }

        return false;
    }
}
