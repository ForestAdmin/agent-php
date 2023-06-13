<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\RenameCollection;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\CollectionDecorator;
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

    public function getFields(): IlluminateCollection
    {
        $fields = collect();

        foreach ($this->childCollection->getFields() as $fieldName => $schema) {
            if ($schema instanceof RelationSchema) {
                $schema->setForeignCollection($this->getNewName($schema->getForeignCollection()));
            }

            $fields->put($fieldName, $schema);
        }

        return $fields;
    }

    public function makeTransformer()
    {
        $transformer = $this->childCollection->makeTransformer();
        $transformer->setName($this->getName());

        return $transformer;
    }

    private function getNewName(string $oldName): string
    {
        return $this->dataSource->getCollections()->first(fn ($c) => $c->childCollection->getName() === $oldName)->getName();
    }
}
