<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Schema;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\CollectionDecorator;

class SchemaCollection extends CollectionDecorator
{
    public function overrideSchema($attribute, $value): void
    {
        $this->$attribute = $value;
        $this->markSchemaAsDirty();
    }
}
