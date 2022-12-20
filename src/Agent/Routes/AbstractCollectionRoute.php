<?php

namespace ForestAdmin\AgentPHP\Agent\Routes;

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;

abstract class AbstractCollectionRoute extends AbstractAuthenticatedRoute
{
    protected CollectionContract $collection;

    protected Filter $filter;

    protected DatasourceContract $datasource;

    abstract public function handleRequest(array $args = []): array;

    public function build(array $args = []): void
    {
        parent::build($args);

        $this->datasource = AgentFactory::get('datasource');
        $this->collection = $this->datasource->getCollection($args['collectionName']);
    }
}
