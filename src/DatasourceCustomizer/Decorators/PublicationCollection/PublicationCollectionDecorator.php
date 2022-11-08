<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\PublicationCollection;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\CollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

class PublicationCollectionDecorator extends CollectionDecorator
{
    protected array $unpublished = [];

    public function changeFieldVisibility(string $name, bool $visible): void
    {
        $field = $this->childCollection->getFields()[$name] ?? throw new ForestException("Unknown field: $name");
        if ($field instanceof ColumnSchema && $field->isPrimaryKey()) {
            throw new ForestException("Cannot hide primary key");
        }

        if ($visible) {
            $this->unpublished[] = $name;
        } else {
            unset($this->unpublished[$name]);
        }

        $this->markSchemaAsDirty();
    }
}
