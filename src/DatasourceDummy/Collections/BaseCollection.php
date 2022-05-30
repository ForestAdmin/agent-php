<?php

namespace ForestAdmin\AgentPHP\DatasourceDummy\Collections;

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\RelationSchema;

class BaseCollection extends Collection
{
    /**
     * @param DatasourceContract              $dataSource
     * @param string                          $name
     * @param ColumnSchema[]|RelationSchema[] $fields
     */
    public function __construct(protected DatasourceContract $dataSource, protected string $name, array $fields)
    {
        parent::__construct($dataSource, $name);
        $this->addFields($fields);
    }
}
