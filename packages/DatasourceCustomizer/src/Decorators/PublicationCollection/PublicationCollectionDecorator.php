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
use Illuminate\Support\Collection as IlluminateCollection;

class PublicationCollectionDecorator extends CollectionDecorator
{
    protected array $blackList = [];

    public function changeFieldVisibility(string $name, bool $visible): void
    {
        $field = $this->childCollection->getFields()[$name] ?? throw new ForestException("Unknown field: $name");
        if ($field instanceof ColumnSchema && $field->isPrimaryKey()) {
            throw new ForestException("Cannot hide primary key");
        }

        if (! $visible) {
            $this->blackList[$name] = $name;
        } else {
            unset($this->blackList[$name]);
        }

        $this->markSchemaAsDirty();
    }

    public function create(Caller $caller, array $data)
    {
        $record = parent::create($caller, $data);
        foreach ($this->blackList as $value) {
            unset($record[$value]);
        }

        return $record;
    }

    public function refineSchema(IlluminateCollection $childSchema): IlluminateCollection
    {
        $fields = collect();

        foreach ($childSchema as $name => $field) {
            if ($this->isPublished($name)) {
                $fields->put($name, $field);
            }
        }

        return $fields;
    }

    private function isPublished(string $name): bool
    {
        // Explicitly hidden
        if (isset($this->blackList[$name])) {
            return false;
        }

        // Implicitly hidden
        $field = $this->childCollection->getFields()[$name] ?? null;

        if ($field instanceof ManyToOneSchema) {
            return (
                $this->dataSource->isPublished($field->getForeignCollection()) &&
                $this->isPublished($field->getForeignKey()) &&
                $this->dataSource->getCollection($field->getForeignCollection())->isPublished($field->getForeignKeyTarget())
            );
        }

        if ($field instanceof OneToOneSchema || $field instanceof OneToManySchema) {
            return (
                $this->dataSource->isPublished($field->getForeignCollection()) &&
                $this->dataSource->getCollection($field->getForeignCollection())->isPublished($field->getOriginKey()) &&
                $this->isPublished($field->getOriginKeyTarget())
            );
        }

        if ($field instanceof ManyToManySchema) {
            return (
                $this->dataSource->isPublished($field->getThroughCollection()) &&
                $this->dataSource->isPublished($field->getForeignCollection()) &&
                $this->dataSource->getCollection($field->getThroughCollection())->isPublished($field->getForeignKey()) &&
                $this->dataSource->getCollection($field->getThroughCollection())->isPublished($field->getOriginKey()) &&
                $this->isPublished($field->getOriginKeyTarget()) &&
                $this->dataSource->getCollection($field->getForeignCollection())->isPublished($field->getForeignKeyTarget())
            );
        }

        return true;
    }
}
