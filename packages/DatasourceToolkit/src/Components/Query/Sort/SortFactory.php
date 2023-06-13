<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Sort;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Sort;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Schema;

class SortFactory
{
    public static function byPrimaryKeys(CollectionContract $collection): Sort
    {
        return new Sort(
            collect(Schema::getPrimaryKeys($collection))
                ->map(fn ($pk) => ['field' => $pk, 'ascending' => true])
                ->toArray()
        );
    }
}
