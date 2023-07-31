<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\PublicationCollection;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
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

        foreach ($this->collections->keys() as $name) {
            if (($includes && ! in_array($name, $includes, true)) || in_array($name, $excludes, true)) {
                $this->removeCollection($name);
            }
        }
        //        $deleted = [];
        //        foreach ($this->collections->keys() as $name) {
        //            if (($includes && ! in_array($name, $includes, true)) || in_array($name, $excludes, true)) {
        //                $deleted[] = $name;
        //            }
        //        }
        //
        //        /** @var PublicationCollectionDecorator $collection */
        //        foreach ($this->collections as $collection) {
        //            foreach ($collection->getFields() as $key => $field) {
        //                if ((! $field instanceof ColumnSchema && in_array($field->getForeignCollection(),  $deleted, true))
        //                    || ($field instanceof ManyToManySchema && in_array($field->getThroughCollection(), $deleted, true))
        //                ) {
        //                    $collection->changeFieldVisibility($key, false);
        //                }
        //            }
        //        }
        //
        //        foreach ($deleted as $name) {
        //            $this->collections->forget($name);
        //        }
    }

    public function removeCollection(string $collectionName): void
    {
        $this->validateCollectionNames([$collectionName]);

        // Delete the collection
        $this->blackList[] = $collectionName;
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
        //        foreach ($names as $name) {
        //            if (! $this->collections->has($name)) {
        //                throw new ForestException("Unknown collection name: $name");
        //            }
        //        }
    }
}
