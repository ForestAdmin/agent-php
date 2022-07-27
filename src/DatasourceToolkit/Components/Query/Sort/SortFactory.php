<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Sort;

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Sort;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Schema;

class SortFactory
{
    public static function byPrimaryKeys(Collection $collection): Sort
    {
        return new Sort(
            Schema::getPrimaryKeys($collection)
        );
    }
}
