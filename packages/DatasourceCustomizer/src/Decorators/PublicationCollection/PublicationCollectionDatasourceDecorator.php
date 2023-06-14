<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\PublicationCollection;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;

class PublicationCollectionDatasourceDecorator extends DatasourceDecorator
{
    public function __construct(DatasourceContract|DatasourceDecorator $childDataSource)
    {
        parent::__construct($childDataSource, PublicationCollectionDecorator::class);
    }

    public function keepCollectionsMatching(array $includes = [], array $excludes = []): void
    {
        $this->validateCollectionNames([...$includes, ...$excludes]);
        $deleted = [];
        foreach ($this->collections->keys() as $name) {
            if (($includes && ! in_array($name, $includes, true)) || in_array($name, $excludes, true)) {
                $deleted[] = $name;
            }
        }

        /** @var PublicationCollectionDecorator $collection */
        foreach ($this->collections as $collection) {
            foreach ($collection->getFields() as $key => $field) {
                if ((! $field instanceof ColumnSchema && in_array($field->getForeignCollection(),  $deleted, true))
                    || ($field instanceof ManyToManySchema && in_array($field->getThroughCollection(), $deleted, true))
                ) {
                    $collection->changeFieldVisibility($key, false);
                }
            }
        }

        foreach ($deleted as $name) {
            $this->collections->forget($name);
        }
    }

    private function validateCollectionNames(array $names): void
    {
        foreach ($names as $name) {
            if (! $this->collections->has($name)) {
                throw new ForestException("Unknown collection name: $name");
            }
        }
    }
}
