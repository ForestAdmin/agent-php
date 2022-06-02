<?php

namespace ForestAdmin\AgentPHP\Agent\Utils\ForestSchema;

use ForestAdmin\AgentPHP\Agent\Concerns\Relation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
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
            'Column'                                            => self::buildColumnSchema($collection, $name),
            'ManyToOne', 'OneToMany', 'ManyToMany', 'OneToOne'  => self::buildRelationSchema($collection, $name),
            default                                             => throw new \Exception('Invalid field type'),
        };

        return [];

//        return Object.entries(schema)
//                .sort()
//                .reduce((sortedSchema, [key, value]) => {
//            sortedSchema[key] = value;
//
//            return sortedSchema;
//        }, {});
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
        //dd($foreignCollection->getName());

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

    public static function buildToManyRelationSchema($relation, $collection, $foreignCollection, $relationSchema): array
    {
        $key = $relation->getForeignKey();

        dd(2,$relation);
        /*
         * const key = relation.foreignKey;
            const keySchema = collection.schema.fields[key] as ColumnSchema;

            return {
              type: keySchema.columnType as PrimitiveTypes,
              defaultValue: keySchema.defaultValue ?? null,
              isFilterable: SchemaGeneratorFields.isForeignCollectionFilterable(foreignCollection),
              isPrimaryKey: Boolean(keySchema.isPrimaryKey),
              isRequired: keySchema.validation?.some(v => v.operator === 'Present') ?? false,
              isSortable: Boolean(keySchema.isSortable),
              validations: FrontendValidationUtils.convertValidationList(keySchema.validation),
              reference: `${foreignCollection.name}.${relation.foreignKeyTarget}`,
              ...baseSchema,
            };
         */
        return [];
    }

    public static function buildOneToOneSchema($relation, $collection, $foreignCollection, $relationSchema): array
    {
        return [];
    }

    public static function buildManyToOneSchema($relation, $collection, $foreignCollection, $relationSchema): array
    {
        return [];
    }
}
