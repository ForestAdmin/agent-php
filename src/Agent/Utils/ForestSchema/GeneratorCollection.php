<?php

namespace ForestAdmin\AgentPHP\Agent\Utils\ForestSchema;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;

class GeneratorCollection
{
    public static function buildSchema(CollectionContract $collection): array
    {
        return [
            'actions'              => $collection->getActions()->map(fn ($action, $name) => GeneratorAction::buildSchema($collection, $name))->values()->toArray(),
            'fields'               => $collection->getFields()->map(fn ($field, $name) => GeneratorField::buildSchema($collection, $name))->values()->toArray(),
            'icon'                 => null,
            'integration'          => null,
            'isReadOnly'           => $collection->getFields()->every(fn ($field) => $field->getType() === 'Column' && $field->isReadOnly()),
            'isSearchable'         => $collection->isSearchable(),
            'isVirtual'            => false,
            'name'                 => $collection->getName(),
            'onlyForRelationships' => false,
            'paginationType'       => 'page',
            'segments'             => $collection->getSegments()->map(fn ($segment) => GeneratorSegment::buildSchema($collection, $segment))->sortBy('name')->toArray(),
        ];
    }
}
