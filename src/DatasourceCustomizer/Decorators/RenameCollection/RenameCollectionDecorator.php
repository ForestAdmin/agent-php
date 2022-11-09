<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\PublicationCollection;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\CollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\RelationSchema;
use Illuminate\Support\Collection as IlluminateCollection;

class RenameCollectionDecorator extends CollectionDecorator
{
    protected ?string $substitutedName;

    public function getName(): string
    {
        return $this->substitutedName ?? $this->childCollection->getName();
    }

    public function rename(string $name): void
    {
        $this->substitutedName = $name;

        /** @var RenameCollectionDecorator $collection */
        foreach ($this->dataSource->getCollections() as $collection) {
            $collection->markSchemaAsDirty();
        }
    }
}
