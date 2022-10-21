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
            Schema::getPrimaryKeys($collection)
        );
    }
}
