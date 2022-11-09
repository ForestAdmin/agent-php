<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\PublicationCollection;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;

class RenameCollectionDatasourceDecorator extends DatasourceDecorator
{
    public function __construct(DatasourceContract|DatasourceDecorator $childDataSource)
    {
        parent::__construct($childDataSource, RenameCollectionDecorator::class);
    }

    public function renameCollection(string $oldName, string $newName): void
    {
        //todo
    }

    private function renameCollections(array $renames): void
    {
        //todo
    }
}
