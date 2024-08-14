<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\RenameCollection;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\CollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\RelationSchema;
use Illuminate\Support\Collection as IlluminateCollection;

class RenameCollectionDecorator extends CollectionDecorator
{
    public function getName(): string
    {
        return $this->dataSource->getCollectionName(parent::getName());
    }

    public function refineSchema(IlluminateCollection $childSchema): IlluminateCollection
    {
        $fields = collect();

        foreach ($childSchema as $fieldName => $schema) {
            if ($schema instanceof RelationSchema && $schema->getType() !== 'PolymorphicManyToOne') {
                $schema->setForeignCollection($this->dataSource->getCollectionName($schema->getForeignCollection()));
                if ($schema instanceof ManyToManySchema) {
                    $schema->setThroughCollection($this->dataSource->getCollectionName($schema->getThroughCollection()));
                }
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
}
