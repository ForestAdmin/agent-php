<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\CollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context\After\HookAfterAggregateContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context\After\HookAfterCreateContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context\After\HookAfterDeleteContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context\After\HookAfterListContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context\After\HookAfterUpdateContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context\Before\HookBeforeAggregateContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context\Before\HookBeforeCreateContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context\Before\HookBeforeDeleteContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context\Before\HookBeforeListContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context\Before\HookBeforeUpdateContext;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;

class HookCollection extends CollectionDecorator
{
    protected array $hooks;

    public function __construct(CollectionDecorator|CollectionContract $childCollection, Datasource $dataSource)
    {
        parent::__construct($childCollection, $dataSource);

        $this->hooks = [
            'List'      => new Hooks(),
            'Create'    => new Hooks(),
            'Update'    => new Hooks(),
            'Delete'    => new Hooks(),
            'Aggregate' => new Hooks(),
        ];
    }

    public function addHook(string $position, string $type, \Closure $handler): void
    {
        $this->hooks[$type]->addHandler($position, $handler);
    }

    public function create(Caller $caller, array $data)
    {
        $beforeContext = new HookBeforeCreateContext($this->childCollection, $caller, $data);
        $this->hooks['Create']->executeBefore($beforeContext);

        $records = $this->childCollection->create($caller, $beforeContext->getData());

        $afterContext = new HookAfterCreateContext($this->childCollection, $caller, $data, $records);
        $this->hooks['Create']->executeAfter($afterContext);

        return $records;
    }

    public function list(Caller $caller, PaginatedFilter $filter, Projection $projection): array
    {
        $beforeContext = new HookBeforeListContext($this->childCollection, $caller, $filter, $projection);
        $this->hooks['List']->executeBefore($beforeContext);

        $records = $this->childCollection->list($caller, $beforeContext->getFilter(), $beforeContext->getProjection());

        $afterContext = new HookAfterListContext($this->childCollection, $caller, $filter, $projection, $records);
        $this->hooks['List']->executeAfter($afterContext);

        return $records;
    }

    public function update(Caller $caller, Filter $filter, array $patch): void
    {
        $beforeContext = new HookBeforeUpdateContext($this->childCollection, $caller, $filter, $patch);
        $this->hooks['Update']->executeBefore($beforeContext);

        $this->childCollection->update($caller, $beforeContext->getFilter(), $beforeContext->getPatch());

        $afterContext = new HookAfterUpdateContext($this->childCollection, $caller, $filter, $patch);
        $this->hooks['Update']->executeAfter($afterContext);
    }

    public function delete(Caller $caller, Filter $filter): void
    {
        $beforeContext = new HookBeforeDeleteContext($this->childCollection, $caller, $filter);
        $this->hooks['Delete']->executeBefore($beforeContext);

        $this->childCollection->delete($caller, $beforeContext->getFilter());

        $afterContext = new HookAfterDeleteContext($this->childCollection, $caller, $filter);
        $this->hooks['Delete']->executeAfter($afterContext);
    }

    public function aggregate(Caller $caller, Filter $filter, Aggregation $aggregation, ?int $limit = null)
    {
        $beforeContext = new HookBeforeAggregateContext($this->childCollection, $caller, $filter, $aggregation, $limit);
        $this->hooks['Aggregate']->executeBefore($beforeContext);

        $results = $this->childCollection->aggregate($caller, $beforeContext->getFilter(), $beforeContext->getAggregation(), $beforeContext->getLimit());

        $afterContext = new HookAfterAggregateContext($this->childCollection, $caller, $filter, $aggregation, $results, $limit);
        $this->hooks['Aggregate']->executeAfter($afterContext);

        return $results;
    }
}
