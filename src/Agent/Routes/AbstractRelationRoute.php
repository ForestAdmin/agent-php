<?php

namespace ForestAdmin\AgentPHP\Agent\Routes;

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Services\ForestAdminHttpDriverServices;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Schema;

abstract class AbstractRelationRoute extends AbstractAuthenticatedRoute
{
    protected Collection $collection;

    protected Collection $childCollection;

    protected Datasource $datasource;

    abstract public function handleRequest(array $args = []): array;

    public function build(array $args = []): void
    {
        parent::build($args);

        $this->datasource = AgentFactory::get('datasource');
        $this->collection = $this->datasource->getCollection($args['collectionName']);
        $this->collection->hydrate($args);

        $relation = Schema::getToManyRelation($this->collection, $args['relationName']);
        $this->childCollection = $this->datasource->getCollection($relation->getForeignCollection());
    }
}
