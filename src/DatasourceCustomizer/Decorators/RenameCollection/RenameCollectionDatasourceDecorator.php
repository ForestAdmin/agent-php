<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\RenameCollection;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

class RenameCollectionDatasourceDecorator extends DatasourceDecorator
{
    public function __construct(DatasourceContract|DatasourceDecorator $childDataSource)
    {
        parent::__construct($childDataSource, RenameCollectionDecorator::class);
    }

    public function renameCollections(array $renames): void
    {
        foreach ($renames as $oldName => $newName) {
            $this->renameCollection($oldName, $newName);
        }
    }

    private function renameCollection(string $oldName, string $newName): void
    {
        if (! $this->collections->has($oldName)) {
            throw new ForestException("The given collection name $oldName does not exist");
        }

        if ($this->collections->has($newName)) {
            throw new ForestException("The given new collection name $newName is already defined in the dataSource");
        }

        if ($oldName !== $newName) {
            /** @var RenameCollectionDecorator $collection */
            $collection = $this->collections[$oldName];
            $collection->rename($newName);

            $this->collections->put($newName, $collection);
            $this->collections->forget($oldName);
        }
    }
}
