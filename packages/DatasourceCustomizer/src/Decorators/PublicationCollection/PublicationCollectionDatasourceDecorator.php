<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\PublicationCollection;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Collection as CollectionUtils;
use Illuminate\Support\Collection as IlluminateCollection;

class PublicationCollectionDatasourceDecorator extends DatasourceDecorator
{
    protected array $blackList = [];

    public function __construct(DatasourceContract|DatasourceDecorator $childDataSource)
    {
        parent::__construct($childDataSource, PublicationCollectionDecorator::class);
    }

    public function getCollections(): IlluminateCollection
    {
        return $this->childDataSource->getCollections()
            ->filter(fn ($collection) => ! in_array($collection->getName(), $this->blackList, true))
            ->map(fn ($collection) => $this->getCollection($collection->getName()));
    }

    public function getCollection(string $name): CollectionContract
    {
        if (in_array($name, $this->blackList, true)) {
            throw new ForestException("Collection $name was removed.");
        }

        return parent::getCollection($name);
    }

    public function keepCollectionsMatching(array $includes = [], array $excludes = []): void
    {
        $this->validateCollectionNames([...$includes, ...$excludes]);

        foreach ($this->getCollections()->keys() as $name) {
            if (($includes && ! in_array($name, $includes, true)) || in_array($name, $excludes, true)) {
                $this->removeCollection($name);
            }
        }
    }

    public function removeCollection(string $collectionName): void
    {
        $this->validateCollectionNames([$collectionName]);
        $this->validateIsRemovable($this->getCollection($collectionName));

        // Delete the collection
        $this->blackList[] = $collectionName;

        // Tell all collections that their schema is dirty: if we removed a collection, all
        // relations to this collection are now invalid and should be unpublished.
        foreach ($this->getCollections() as $collection) {
            $collection->markSchemaAsDirty();
        }
    }

    public function isPublished(string $collectionName): bool
    {
        return ! in_array($collectionName, $this->blackList, true);
    }

    private function validateCollectionNames(array $names): void
    {
        foreach ($names as $name) {
            $this->getCollection($name);
        }
    }

    private function validateIsRemovable(CollectionContract $collection): void
    {
        foreach ($collection->getFields() as $fieldName => $fieldSchema) {
            if ($fieldSchema->getType() === 'PolymorphicOneToOne' || $fieldSchema->getType() === 'PolymorphicOneToMany') {
                $inverse = CollectionUtils::getInverseRelation($collection, $fieldName);

                throw new ForestException(
                    "Cannot remove {$collection->getName()} because it's a potential target of polymorphic relation {$fieldSchema->getForeignCollection()}.$inverse"
                );
            }
        }
    }
}
