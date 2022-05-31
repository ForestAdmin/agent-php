<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit;

class OneToManySchema extends SingleRelation
{
    protected string $type = 'OneToMany';

    public function getFormat(): array
    {
        return [
            'foreignCollection' => $this->foreignCollection,
            'originKey'         => $this->originKey,
            'originKeyTarget'   => $this->originKeyTarget,
            'type'              => $this->type,
        ];
    }
}
