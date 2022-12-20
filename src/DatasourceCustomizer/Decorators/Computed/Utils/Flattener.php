<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Computed\Utils;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Record;

class Flattener
{
    public static function flatten($records, Projection $projection): array
    {
        return $projection->map(
            fn ($field) => collect($records)->map(fn ($record) => Record::getFieldValue($record, $field))->all()
        )->all();
    }

    public static function unFlatten(array $flatten, Projection $projection): array
    {
        $numRecords = count($flatten[0]) ?? 0;
        $records = array_fill(0, $numRecords, []);
        foreach ($projection->columns() as $index => $field) {
            foreach ($flatten[$index] as $key => $value) {
                $records[$key][$field] = $value ?? null;
            }
        }

        foreach ($projection->relations() as $relation => $paths) {
            $subFlatten = [];
            foreach ($paths as $path) {
                $subFlatten[] = $flatten[$projection->search("$relation:$path")];
            }

            $subRecords = self::unFlatten($subFlatten, $paths);
            foreach ($records as $key => $value) {
                $records[$key][$relation] = $subRecords[$key];
            }
        }

        return collect($records)->map(
            fn ($record) => collect($record)->some(fn ($value) => $value !== null) ? $record : null
        )->toArray();
    }
}
