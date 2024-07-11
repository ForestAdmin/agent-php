<?php

namespace ForestAdmin\AgentPHP\Agent\Routes;

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\PolymorphicManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\PolymorphicOneToOneSchema;

abstract class AbstractRelationRoute extends AbstractAuthenticatedRoute
{
    protected CollectionContract $collection;

    protected CollectionContract $childCollection;

    protected Datasource $datasource;

    abstract public function handleRequest(array $args = []): array;

    public function build(array $args = []): void
    {
        parent::build($args);

        $this->datasource = AgentFactory::get('datasource');
        $this->collection = $this->datasource->getCollection($args['collectionName']);

        $relation = $this->collection->getFields()[$args['relationName']];

        if ($relation instanceof PolymorphicManyToOneSchema || $relation instanceof PolymorphicOneToOneSchema) {
            // Patch Frontend on Polymorphic OneToOne
            $type = ucfirst($this->request->input('data.type'));

            $this->childCollection = $this->datasource->getCollection($type);
        } else {
            $this->childCollection = $this->datasource->getCollection($relation->getForeignCollection());
        }
    }
}
