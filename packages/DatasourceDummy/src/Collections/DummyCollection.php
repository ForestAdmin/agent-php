<?php

namespace ForestAdmin\AgentPHP\DatasourceDummy\Collections;

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\RelationSchema;

class DummyCollection extends Collection
{
    protected $records = [];

    /**
     * @param DatasourceContract $dataSource
     * @param string                                                               $name
     * @param ColumnSchema[]|RelationSchema[]                                      $fields
     */
    public function __construct(protected DatasourceContract $dataSource, protected string $name, array $fields)
    {
        parent::__construct($dataSource, $name);
        $this->addFields($fields);
    }

    public function list(Caller $caller, Filter $filter, Projection $projection): array
    {
        return $this->records;
    }
}
