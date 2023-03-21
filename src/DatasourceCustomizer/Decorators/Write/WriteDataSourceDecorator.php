<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Write;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Write\CreateRelations\CreateCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Write\UpdateRelations\UpdateCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Write\WriteReplace\WriteReplaceCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;

class WriteDataSourceDecorator extends DatasourceDecorator
{
    protected DatasourceDecorator $create;

    protected DatasourceDecorator $update;

    public function __construct(DatasourceContract|DatasourceDecorator $childDataSource)
    {
        parent::__construct($childDataSource, WriteReplaceCollection::class);
        $this->create = new DatasourceDecorator($this->childDataSource, CreateCollection::class);
        $this->update = new DatasourceDecorator($this->childDataSource, UpdateCollection::class);
    }

    public function build()
    {
        parent::build();
        $this->create->build();
        $this->update->build();
    }
}
