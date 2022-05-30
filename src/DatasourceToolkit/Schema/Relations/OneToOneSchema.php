<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations;

class OneToOneSchema extends SingleRelationSchema
{
    protected string $type = 'OneToOne';

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
