<?php

namespace ForestAdmin\AgentPHP\DatasourceEloquent\Utils;

use ForestAdmin\AgentPHP\BaseDatasource\Contracts\BaseCollectionContract;
use ForestAdmin\AgentPHP\BaseDatasource\Utils\QueryBuilder as BaseQueryBuilder;
use ForestAdmin\AgentPHP\DatasourceEloquent\EloquentCollection;
use Illuminate\Database\Query\JoinClause;

class QueryBuilder extends BaseQueryBuilder
{
    public function __construct(
        protected BaseCollectionContract $collection,
    ) {
        parent::__construct($collection);
        /** @var EloquentCollection $collection */
        $this->query = $collection->model->newQuery();
    }

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
