<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\RenameCollection;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Collection as CollectionUtils;
use Illuminate\Support\Collection as IlluminateCollection;

class RenameCollectionDatasourceDecorator extends DatasourceDecorator
{
    protected array $toChildName = [];

    protected array $fromChildName = [];

    public function __construct(DatasourceContract|DatasourceDecorator $childDataSource)
    {
        parent::__construct($childDataSource, RenameCollectionDecorator::class);
    }

    public function getCollections(): IlluminateCollection
    {
        return $this->childDataSource->getCollections()->map(fn ($collection) => parent::getCollection($collection->getName()));
    }

    public function getCollection(string $name): RenameCollectionDecorator
    {
        // Collection has been renamed, user is using the new name
        if (isset($this->toChildName[$name])) {
            return parent::getCollection($this->toChildName[$name]);
        }

        // Collection has been renamed, user is using the old name
        if (isset($this->fromChildName[$name])) {
            throw new ForestException("Collection '$name' has been renamed to '{$this->fromChildName[$name]}'");
        }

        return parent::getCollection($name);
    }

    public function getCollectionName(string $childName): string
    {
        return $this->fromChildName[$childName] ?? $childName;
    }

    public function renameCollections(array $renames): void
    {
        foreach ($renames as $oldName => $newName) {
            $this->renameCollection($oldName, $newName);
        }
    }

    private function renameCollection(string $currentName, string $newName): void
    {
        // Check collection exists
        $this->getCollection($currentName);

        // Rename collection
        if ($currentName !== $newName) {
            // Check new name is not already used
            if ($this->getCollections()->some(fn ($collection) => $collection->getName() === $newName)) {
                throw new ForestException("The given new collection name $newName is already defined in the dataSource");
            }

            // Check we don't rename a collection twice
            if (isset($this->toChildName[$currentName])) {
                throw new ForestException("Cannot rename a collection twice: {$this->toChildName[$currentName]}->{$currentName}->{$newName}");
            }

            $this->getCollection($currentName)->getFields()->each(function ($fieldSchema, $fieldName) use ($currentName) {
                if ($fieldSchema->getType() === 'PolymorphicOneToOne' || $fieldSchema->getType() === 'PolymorphicOneToMany') {
                    $reverseRelationName = CollectionUtils::getInverseRelation($this->getCollection($currentName), $fieldName);

                    throw new ForestException("Cannot rename collection $currentName because it's a target of a polymorphic relation '{$fieldSchema->getForeignCollection()}.{$reverseRelationName}'");
                }
            });

            $this->fromChildName[$currentName] = $newName;
            $this->toChildName[$newName] = $currentName;

            foreach ($this->collections as $collection) {
                $collection->markSchemaAsDirty();
            }
        }
    }
}
