<?php

namespace ForestAdmin\AgentPHP\Agent\Utils\ForestSchema;

use ForestAdmin\AgentPHP\Agent\Concerns\Relation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\RelationSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Collection as CollectionUtils;

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
            'isFilterable' => false, // TODO FrontendFilterableUtils . isFilterable(column . columnType, column . filterOperators),
            'isPrimaryKey' => $column->isPrimaryKey(),
            'isReadOnly'   => $column->isReadOnly(),
            'isRequired'   => true, // TODO column . validation ?.some(v => v . operator === 'Present') ?? false,
            'isSortable'   => $column->isSortable(),
            'isVirtual'    => false,
            'reference'    => null,
            'type'         => self::convertColumnType($column->getColumnType()),
            'validations'  => '', // TODO  FrontendValidationUtils . convertValidationList(column . validation),
        ];
    }

    public static function buildRelationSchema(Collection $collection, string $name): array
    {
        /** @var RelationSchema $relation */
        $relation = $collection->getFields()->get($name);
        $foreignCollection = $collection->getDataSource()->getCollections()->first(fn ($item) => $item->getName() === $relation->getForeignCollection());

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
            $keySchema = $collection->getFields()->get($key);
        } else {
            $key = $relation->getForeignKeyTarget();
            $keySchema = $foreignCollection->getFields()->get($key);
        }

        return array_merge(
            $baseSchema,
            [
                'type'         => '[' . $keySchema->getColumnType()->value . ']',
                'defaultValue' => null, // TODO QUESTION SEE buildManyToOneSchema DEFAULTVALUE
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
        /** @var ColumnSchema $keySchema */
        $keySchema = $collection->getFields()->get($key);

        return array_merge(
            $baseSchema,
            [
                'type'         => $keySchema->getColumnType()->value,
                'defaultValue' => null, // TODO QUESTION SEE buildManyToOneSchema DEFAULTVALUE
                'isFilterable' => false, // TODO SchemaGeneratorFields . isForeignCollectionFilterable(foreignCollection),
                'isPrimaryKey' => false,
                'isRequired'   => false,
                'isSortable'   => $keySchema->isSortable(),
                'validations'  => [],
                'reference'    => $foreignCollection->getName() . '.' . $key,
            ],
        );
    }

    public static function buildManyToOneSchema(RelationSchema $relation, Collection $collection, Collection $foreignCollection, array $baseSchema): array
    {
        $key = $relation->getForeignKey();
        /** @var ColumnSchema $keySchema */
        $keySchema = $collection->getFields()->get($key);

        return array_merge(
            $baseSchema,
            [
                'type'         => $keySchema->getColumnType()->value,
                'defaultValue' => null, // TODO QUESTION SEE buildManyToOneSchema DEFAULTVALUE
                'isFilterable' => false, //  SchemaGeneratorFields.isForeignCollectionFilterable(foreignCollection),
                'isPrimaryKey' => $keySchema->isPrimaryKey(),
                'isRequired'   => false, // TODO  keySchema.validation?.some(v => v.operator === 'Present') ?? false,
                'isSortable'   => $keySchema->isSortable(),
                'validations'  => [], //  FrontendValidationUtils.convertValidationList(keySchema.validation),
                'reference'    => $foreignCollection->getName() . '.' . $key,
            ],
        );

    }
}
