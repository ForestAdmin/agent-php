<?php

namespace ForestAdmin\AgentPHP\Agent\Utils\ForestSchema;

use ForestAdmin\AgentPHP\Agent\Concerns\Relation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\RelationSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Collection as CollectionUtils;
use function ForestAdmin\cache;

class GeneratorField
{
    /**
     * @throws \Exception
     */
    public static function buildSchema(Collection $collection, string $name): array
    {
        $field = $collection->getFields()->get($name);

        $fieldSchema = match ($field->getType()) {
            'Column'                                           => self::buildColumnSchema($collection, $name),
            'ManyToOne', 'OneToMany', 'ManyToMany', 'OneToOne' => self::buildRelationSchema($collection, $name),
            default                                            => throw new \Exception('Invalid field type'),
        };
        ksort($fieldSchema);

        return $fieldSchema;
    }

    public static function buildColumnSchema(Collection $collection, string $name)
    {
        /** @var ColumnSchema $column */
        $column = $collection->getFields()->get($name);

        return [
            'defaultValue' => $column->getDefaultValue(),
            'enums'        => $column->getEnumValues(),
            'field'        => $name,
            'integration'  => null,
            'inverseOf'    => null,
            'isFilterable' => FrontendFilterable::isFilterable($column->getColumnType(), $column->getFilterOperators()),
            'isPrimaryKey' => $column->isPrimaryKey(),
            'isReadOnly'   => $column->isReadOnly(),
            'isRequired'   => in_array('Present', $column->getValidation(), true),
            'isSortable'   => $column->isSortable(),
            'isVirtual'    => false,
            'reference'    => null,
            'type'         => self::convertColumnType($column->getColumnType()),
            'validations'  => FrontendValidation::convertValidationList($column->getValidation()),
        ];
    }

    public static function buildRelationSchema(Collection $collection, string $name): array
    {
        /** @var RelationSchema $relation */
        $relation = $collection->getFields()->get($name);
        $foreignCollection = cache('datasource')->getCollections()->first(fn ($item) => $item->getName() === $relation->getForeignCollection());

        $relationSchema = [
            'field'        => $name,
            'enums'        => null,
            'integration'  => null,
            'isReadOnly'   => false,
            'isVirtual'    => false,
            'inverseOf'    => CollectionUtils::getInverseRelation($collection, $name),
            'relationship' => Relation::getRelation($relation->getType()),
        ];

        return match ($relation->getType()) {
            'ManyToMany', 'OneToMany' => self::buildToManyRelationSchema($relation, $collection, $foreignCollection, $relationSchema),
            'OneToOne'                => self::buildOneToOneSchema($relation, $collection, $foreignCollection, $relationSchema),
            default                   => self::buildManyToOneSchema($relation, $collection, $foreignCollection, $relationSchema),
        };
    }

    public static function convertColumnType($columnType)
    {
        if (gettype($columnType) === 'string') {
            return $columnType;
        }

        if (is_array($columnType)) {
            return [self::convertColumnType($columnType)];
        }
    }

    public static function buildToManyRelationSchema(RelationSchema $relation, Collection $collection, Collection $foreignCollection, array $baseSchema): array
    {
        if (is_a($relation, OneToManySchema::class)) {
            $key = $relation->getOriginKeyTarget();
            /** @var ColumnSchema $keySchema */
            $column = $collection->getFields()->get($key);
        } else {
            $key = $relation->getForeignKeyTarget();
            $column = $foreignCollection->getFields()->get($key);
        }

        return array_merge(
            $baseSchema,
            [
                'type'         => '[' . $column->getColumnType()->value . ']',
                'defaultValue' => null,
                'isFilterable' => false,
                'isPrimaryKey' => false,
                'isRequired'   => false,
                'isSortable'   => false,
                'validations'  => [],
                'reference'    => $foreignCollection->getName() . '.' . $key,
            ],
        );
    }

    public static function buildOneToOneSchema(RelationSchema $relation, Collection $collection, Collection $foreignCollection, array $baseSchema): array
    {
        $key = $relation->getOriginKeyTarget();
        /** @var ColumnSchema $column */
        $column = $collection->getFields()->get($key);

        return array_merge(
            $baseSchema,
            [
                'type'         => $column->getColumnType()->value,
                'defaultValue' => null,
                'isFilterable' => self::isForeignCollectionFilterable($foreignCollection),
                'isPrimaryKey' => false,
                'isRequired'   => false,
                'isSortable'   => $column->isSortable(),
                'validations'  => [],
                'reference'    => $foreignCollection->getName() . '.' . $key,
            ],
        );
    }

    public static function buildManyToOneSchema(RelationSchema $relation, Collection $collection, Collection $foreignCollection, array $baseSchema): array
    {
        $key = $relation->getForeignKey();
        /** @var ColumnSchema $column */
        $column = $collection->getFields()->get($key);

        return array_merge(
            $baseSchema,
            [
                'type'         => $column->getColumnType()->value,
                'defaultValue' => null,
                'isFilterable' => self::isForeignCollectionFilterable($foreignCollection),
                'isPrimaryKey' => $column->isPrimaryKey(),
                'isRequired'   => in_array('Present', $column->getValidation(), true),
                'isSortable'   => $column->isSortable(),
                'validations'  => FrontendValidation::convertValidationList($column->getValidation()),
                'reference'    => $foreignCollection->getName() . '.' . $key,
            ],
        );
    }

    public static function isForeignCollectionFilterable(Collection $foreignCollection): bool
    {
        return $foreignCollection->getFields()->some(
            fn ($column) => $column->getType() === 'Column' && FrontendFilterable::isFilterable($column->getColumnType(), $column->getFilterOperators())
        );
    }
}
