<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations;

class ManyToOneSchema extends ManyRelationSchema
{
    protected string $type = 'ManyToOne';

    public function getFormat(): array
    {
        return [
            'foreignCollection' => $this->foreignCollection,
            'foreignKey'        => $this->foreignKey,
            'foreignKeyTarget'  => $this->foreignKeyTarget,
            'type'              => $this->type,
        ];
    }
}
