<?php

namespace ForestAdmin\AgentPHP\Agent\Routes;

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;

abstract class AbstractCollectionRoute extends AbstractAuthenticatedRoute
{
    protected Collection $collection;

    protected Filter $filter;

    protected Datasource $datasource;

    abstract public function handleRequest(array $args = []): array;

    public function build(array $args = []): void
    {
        parent::build($args);

        $this->datasource = AgentFactory::get('datasource');
        $this->collection = $this->datasource->getCollection($args['collectionName']);
        $this->collection->hydrate($args);
    }
}
