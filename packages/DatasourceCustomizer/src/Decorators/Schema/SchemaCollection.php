<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Schema;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\CollectionDecorator;

class SchemaCollection extends CollectionDecorator
{
    private array $schemaOverride = [];

    public function overrideSchema($attribute, $value): void
    {
        $this->schemaOverride[$attribute] = $value;
        $this->markSchemaAsDirty();
    }

    public function isCountable(): bool
    {
        if (isset($this->schemaOverride['countable']) && ! $this->schemaOverride['countable']) {
            return false;
        }

        return parent::isCountable();
    }
}
