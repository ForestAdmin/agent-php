<?php

namespace ForestAdmin\AgentPHP\Agent\Routes;

use ForestAdmin\AgentPHP\Agent\Services\ForestAdminHttpDriverServices;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\RelationSchema;

abstract class AbstractRelationRoute extends AbstractCollectionRoute
{
    public function __construct(
        protected ForestAdminHttpDriverServices $services,
        protected array $options,
        protected DatasourceContract $datasource,
        protected string $collectionName,
        protected string $relationName

    ) {
        parent::__construct($services, $this->options, $this->datasource, $this->collectionName);
    }

    protected function getForeignCollection(): RelationSchema
    {
        return $this->getCollection()->getFields()->get($this->relationName);
    }
}
