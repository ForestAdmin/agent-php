<?php

namespace ForestAdmin\AgentPHP\Agent\Routes;

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Services\ForestAdminHttpDriverServices;
use ForestAdmin\AgentPHP\Agent\Utils\ContextFilterFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Schema;

abstract class AbstractRelationRoute extends AbstractRoute
{
    protected Collection $childCollection;

    public function __construct(protected ForestAdminHttpDriverServices $services)
    {
        parent::__construct($services);
    }

    abstract public function handleRequest(array $args = []): array;

    public function build(array $args = []): void
    {
        $this->datasource = AgentFactory::get('datasource');
        $this->collection = $this->datasource->getCollection($args['collectionName']);
        $this->collection->hydrate($args);
        $this->request = Request::createFromGlobals();
        $scope = null; // todo merge

        $relation = Schema::getToManyRelation($this->collection, $args['relationName']);
        $this->childCollection = $this->datasource->getCollection($relation->getForeignCollection());
        $this->filter = ContextFilterFactory::buildPaginated($this->childCollection, $this->request, $scope);
    }
}
