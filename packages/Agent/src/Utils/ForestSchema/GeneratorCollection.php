<?php

namespace ForestAdmin\AgentPHP\Agent\Utils\ForestSchema;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Schema as SchemaUtils;

class GeneratorCollection
{
    public static function buildSchema(CollectionContract $collection): array
    {
        return [
            'actions'              => $collection->getActions()->map(fn ($action, $name) => GeneratorAction::buildSchema($collection, $name))->sortBy('id')->values()->toArray(),
            'fields'               => self::buildFields($collection),
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

    public static function buildFields(CollectionContract $collection): array
    {
        return $collection
            ->getSchema()
            ->filter(function ($field, $name) use ($collection) {
                return ! SchemaUtils::isForeignKey($collection, $name) || SchemaUtils::isPrimaryKey($collection, $name);
            })
            ->map(fn ($field, $name) => GeneratorField::buildSchema($collection, $name))
            ->sortBy('field')
            ->values()
            ->toArray();
    }
}
