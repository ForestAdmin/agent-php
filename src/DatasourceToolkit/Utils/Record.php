<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Utils;

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\RelationSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

class Record
{
    public static function getPrimaryKeys(Collection $schema, array $record): array
    {
        return collect(Schema::getPrimaryKeys($schema))->map(
            fn ($pk) => $record[$pk] ?? throw new ForestException("Missing primary key: $pk")
        )->toArray();
    }
}
