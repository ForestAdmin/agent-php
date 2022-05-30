<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations;

class ManyToManySchema extends ManyRelationSchema
{
    protected string $type = 'ManyToMany';

    private string $throughCollection;

    private string $originKey;

    private string $originKeyTarget;

    public function getFormat(): array
    {
        return [
            'throughCollection' => $this->throughCollection,
            'foreignCollection' => $this->foreignCollection,
            'foreignKey' => $this->foreignKey,
            'foreignKeyTarget' => $this->foreignKeyTarget,
            'originKey' => $this->originKey,
            'originKeyTarget' => $this->originKeyTarget,
            'type' => $this->type,
        ];
    }
}
