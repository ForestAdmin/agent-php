<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Write;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Write\CreateRelations\CreateRelationsCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Write\UpdateRelations\UpdateRelationsCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Write\WriteReplace\WriteReplaceCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;

class WriteDataSourceDecorator extends DatasourceDecorator
{
    protected DatasourceDecorator $create;

    protected DatasourceDecorator $update;

    public function __construct(DatasourceContract|DatasourceDecorator $childDataSource)
    {
        parent::__construct($childDataSource, WriteReplaceCollection::class);
        $this->create = new DatasourceDecorator($this->childDataSource, CreateRelationsCollection::class);
        $this->update = new DatasourceDecorator($this->childDataSource, UpdateRelationsCollection::class);
    }

    public function build()
    {
        parent::build();
        $this->create->build();
        $this->update->build();
    }
}
