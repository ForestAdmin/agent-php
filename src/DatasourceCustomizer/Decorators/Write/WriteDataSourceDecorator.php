<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Write;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Write\CreateRelations\CreateRelationsCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Write\UpdateRelations\UpdateRelationsCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Write\WriteReplace\WriteReplaceCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;

class WriteDataSourceDecorator extends DatasourceDecorator
{
    protected DatasourceDecorator $createRelations;

    protected DatasourceDecorator $updateRelations;

    public function __construct(DatasourceContract|DatasourceDecorator $childDataSource)
    {
        parent::__construct($childDataSource, WriteReplaceCollection::class);
        $this->createRelations = new DatasourceDecorator($this->childDataSource, CreateRelationsCollection::class);
        $this->updateRelations = new DatasourceDecorator($this->childDataSource, UpdateRelationsCollection::class);
    }

    public function build()
    {
        $this->createRelations->build();
        $this->updateRelations->build();
        parent::build();
    }

    /**
     * @param string $collectionName
     * @return CreateRelationsCollection
     */
    public function getCreateRelationsOfCollection(string $collectionName): CreateRelationsCollection
    {
        return $this->createRelations->getCollection($collectionName);
    }

    /**
     * @param string $collectionName
     * @return UpdateRelationsCollection
     */
    public function getUpdateRelationsOfCollection(string $collectionName): UpdateRelationsCollection
    {
        return $this->updateRelations->getCollection($collectionName);
    }
}
