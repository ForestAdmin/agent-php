<?php

namespace ForestAdmin\AgentPHP\Agent\Utils\ForestSchema;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;

class GeneratorSegment
{
    public static function buildSchema(CollectionContract $collection, string $name): array
    {
        return [
            'id'   => $collection->getName() . '.' . $name,
            'name' => $name,
        ];
    }
}
