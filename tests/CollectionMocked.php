<?php

namespace ForestAdmin\AgentPHP\Tests;

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use Illuminate\Support\Collection as IlluminateCollection;

class CollectionMocked extends Collection
{
    public $paramsUpdate;

    public $createReturn;

    public $listReturn;

    public function __construct(
        protected DatasourceContract $dataSource,
        protected string $name,
    ) {
        $this->fields = new IlluminateCollection();
        $this->actions = new IlluminateCollection();
        $this->segments = new IlluminateCollection();
    }

    public function list(Caller $caller, PaginatedFilter|Filter $filter, Projection $projection): array
    {
        return $this->listReturn;
    }

    public function create(Caller $caller, array $data)
    {
        return $this->createReturn;
    }

    public function update(Caller $caller, Filter $filter, array $patch)
    {
        $this->paramsUpdate = compact('caller', 'filter', 'patch');
    }
}
