<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Utils;

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Record
{
    public static function getPrimaryKeys(Collection $schema, array $record): array
    {
        return collect(Schema::getPrimaryKeys($schema))->map(
            fn ($pk) => $record[$pk] ?? throw new ForestException("Missing primary key: $pk")
        )->toArray();
    }

    public static function getFieldValue(array $record, string $field)
    {
        $record = Arr::dot($record);
        $path = Str::replace(':', '.', $field);

        return Arr::get($record, $path);
    }
}
