<?php

namespace ForestAdmin\AgentPHP\Agent\Utils\ForestSchema;

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;

class GeneratorCollection
{
    public static function buildSchema(string $prefix, Collection $collection): array
    {
        return [
            'actions'              => [],
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


//      actions: await Promise.all(
//        Object.keys(collection.schema.actions)
//          .sort()
//          .map(name => SchemaGeneratorActions.buildSchema(prefix, collection, name)),
//      ),
//      fields: Object.keys(collection.schema.fields)
//        .filter(name => !SchemaUtils.isForeignKey(collection.schema, name))
//        .sort()
//        .map(name => SchemaGeneratorFields.buildSchema(collection, name)),
//}
}
