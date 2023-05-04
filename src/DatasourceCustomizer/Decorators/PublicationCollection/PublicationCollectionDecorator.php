<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\PublicationCollection;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\CollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\RelationSchema;
use Illuminate\Support\Collection as IlluminateCollection;

class PublicationCollectionDecorator extends CollectionDecorator
{
    protected array $unpublished = [];

    public function changeFieldVisibility(string $name, bool $visible): void
    {
        $field = $this->childCollection->getFields()[$name] ?? throw new ForestException("Unknown field: $name");
        if ($field instanceof ColumnSchema && $field->isPrimaryKey()) {
            throw new ForestException("Cannot hide primary key");
        }

        if (! $visible) {
            $this->unpublished[$name] = $name;
        } else {
            unset($this->unpublished[$name]);
        }

        $this->markSchemaAsDirty();
    }

    public function create(Caller $caller, array $data)
    {
        $record = parent::create($caller, $data);
        foreach ($this->unpublished as $value) {
            unset($record[$value]);
        }

        return $record;
    }

    public function getFields(): IlluminateCollection
    {
        $fields = collect();

        foreach ($this->childCollection->getFields() as $name => $field) {
            if ($this->isPublished($name)) {
                $fields->put($name, $field);
            }
        }

        return $fields;
    }

    private function isPublished(string $name): bool
    {
        if ($field = $this->childCollection->getFields()[$name] ?? null) {
            if ($field instanceof ColumnSchema) {
                return ! isset($this->unpublished[$name]);
            } else {
                return $this->isPublishedRelation($name, $field);
            }
        }

        return false;
    }

    private function isPublishedRelation(string $name, RelationSchema $field): bool
    {
        return ! isset($this->unpublished[$name])
            && (
                ($field instanceof ManyToOneSchema && $this->isPublished($field->getForeignKey()))
                || (
                    ($field instanceof OneToOneSchema || $field instanceof  OneToManySchema) &&
                    $this->dataSource->getCollection($field->getForeignCollection())->isPublished($field->getOriginKey())
                )
                || (
                    $field instanceof ManyToManySchema && (
                        $this->dataSource->getCollection($field->getThroughCollection())->isPublished($field->getForeignKey())
                        || $this->dataSource->getCollection($field->getThroughCollection())->isPublished($field->getOriginKey())
                    )
                )
            );
    }
}
