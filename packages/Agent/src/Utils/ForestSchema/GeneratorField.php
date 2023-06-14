<?php

namespace ForestAdmin\AgentPHP\Agent\Utils\ForestSchema;

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Concerns\Relation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\RelationSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Collection as CollectionUtils;

class GeneratorField
{
    /**
     * @throws \Exception
     */
    public static function buildSchema(CollectionContract $collection, string $name): array
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

    public static function buildColumnSchema(CollectionContract $collection, string $name)
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
            'validations'  => FrontendValidation::convertValidationList($column),
        ];
    }

    public static function buildRelationSchema(CollectionContract $collection, string $name): array
    {
        /** @var RelationSchema $relation */
        $relation = $collection->getFields()->get($name);
        $foreignCollection = AgentFactory::get('datasource')->getCollections()->first(fn ($item) => $item->getName() === $relation->getForeignCollection());

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
            default                   => self::buildManyToOneSchema($relation, $foreignCollection, $relationSchema),
        };
    }

    public static function convertColumnType($columnType)
    {
        if (gettype($columnType) === 'string') {
            return $columnType;
        }

        if (isset($columnType[0])) {
            return [self::convertColumnType($columnType[0])];
        }

        return [
            'fields' => collect($columnType)->map(fn ($subType, $key) => ['field' => $key, 'type' => self::convertColumnType($subType)])->values(),
        ];
    }

    public static function buildToManyRelationSchema(RelationSchema $relation, CollectionContract $collection, CollectionContract $foreignCollection, array $baseSchema): array
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
                'type'         => [$column->getColumnType()],
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

    public static function buildOneToOneSchema(OneToOneSchema $relation, CollectionContract $collection, CollectionContract $foreignCollection, array $baseSchema): array
    {
        $key = $relation->getOriginKeyTarget();
        /** @var ColumnSchema $column */
        $column = $collection->getFields()->get($relation->getOriginKeyTarget());
        if ($column === null) {
            // inverse OneToOne case
            $column = $collection->getFields()->get($relation->getOriginKey());
        }

        return array_merge(
            $baseSchema,
            [
                'type'         => $column->getColumnType(),
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

    public static function buildManyToOneSchema(ManyToOneSchema $relation, CollectionContract $foreignCollection, array $baseSchema): array
    {
        $key = $relation->getForeignKeyTarget();
        $foreignTargetColumn = $foreignCollection->getFields()->get($key);

        return array_merge(
            $baseSchema,
            [
                'type'         => $foreignTargetColumn->getColumnType() ,
                'defaultValue' => null,
                'isFilterable' => self::isForeignCollectionFilterable($foreignCollection),
                'isPrimaryKey' => false,
                'isRequired'   => false,
                'isSortable'   => true,
                'validations'  => FrontendValidation::convertValidationList($foreignTargetColumn),
                'reference'    => $foreignCollection->getName() . '.' . $key,
            ],
        );
    }

    public static function isForeignCollectionFilterable(CollectionContract $foreignCollection): bool
    {
        return $foreignCollection->getFields()->some(
            fn ($column) => $column->getType() === 'Column' && FrontendFilterable::isFilterable($column->getColumnType(), $column->getFilterOperators())
        );
    }
}
