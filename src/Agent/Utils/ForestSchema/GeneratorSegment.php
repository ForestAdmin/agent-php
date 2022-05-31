<?php

namespace ForestAdmin\AgentPHP\Agent\Utils\ForestSchema;

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;

class GeneratorSegment
{
    public static function buildSchema(Collection $collection, string $name): array
    {
        return [
            'id'   => $collection->getName() . '.' . $name,
            'name' => $name,
        ];
    }
}
