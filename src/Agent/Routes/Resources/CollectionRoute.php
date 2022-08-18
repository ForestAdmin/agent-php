<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources;

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractAuthenticatedRoute;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;

abstract class CollectionRoute extends AbstractAuthenticatedRoute
{
    protected Collection $collection;

    protected Filter $filter;

    public function __construct()
    {
        parent::__construct();
    }

    abstract public function handleRequest(array $args = []): array;

    public function build(array $args = []): void
    {
        parent::build($args);

        $datasource = AgentFactory::get('datasource');
        $this->collection = $datasource->getCollection($args['collectionName']);
        $this->collection->hydrate($args);
    }
}
