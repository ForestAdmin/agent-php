<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Validations;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use Illuminate\Support\Str;

class FieldValidator
{
    /**
     * @throws ForestException
     */
    public static function validate(CollectionContract $collection, string $field, $values = null)
    {
        if (! Str::contains($field, ':')) {
            $column = $collection->getFields()->get($field);
            if (! $column) {
                throw new ForestException('Column not found: ' . $collection->getName() . '.' . $field);
            }

            if ($column->getType() !== 'Column') {
                throw new ForestException(
                    'Unexpected field type: ' .
                    $collection->getName() . '.' . $field .
                    ' (found ' . $column->getType() . ' expected \'Column\')'
                );
            }

            if (is_array($values)) {
                foreach ($values as $value) {
                    self::validateValue($field, $column, $value);
                }
            }
        } else {
            $prefix = Str::before($field, ':');
            $suffix = Str::after($field, ':');
            $relation = $collection->getFields()->get($prefix);

            if (! $relation) {
                throw new ForestException('Relation not found: ' . $collection->getName() . '.' . $prefix);
            }

            if ($relation->getType() === 'PolymorphicManyToOne' && $suffix !== '*') {
                throw new ForestException(
                    'Unexpected nested field ' . $suffix .
                    ' under generic relation: ' . $collection->getName() . '.' . $prefix
                );
            }

            if (! in_array($relation->getType(), ['ManyToOne', 'OneToOne', 'PolymorphicManyToOne', 'PolymorphicOneToOne'], true)) {
                throw new ForestException(
                    'Unexpected field type: ' .
                    $collection->getName() . '.' . $prefix .
                    ' (found ' . $relation->getType()
                );
            }

            if ($relation->getType() === 'PolymorphicManyToOne') {
                return true;
            }

            $suffix = Str::after($field, ':');
            $association = $collection->getDataSource()->getCollection($relation->getForeignCollection());
            self::validate($association, $suffix, $values);
        }
    }

    /**
     * @throws Exception
     */
    public static function validateValue(string $field, ColumnSchema $columnSchema, $value, ?array $allowedTypes = null): void
    {
        if ($allowedTypes === null) {
            $allowedTypes = Rules::getAllowedTypesForColumnType($columnSchema->getColumnType());
        }

        // TODO FIXME: handle complex type from ColumnType
        if (gettype($columnSchema->getColumnType()) !== PrimitiveType::STRING) {
        }

        $type = TypeGetter::get($value, $columnSchema->getColumnType());
        if ($columnSchema->getColumnType() === PrimitiveType::ENUM) {
            self::checkEnumValue($columnSchema, $value);
        }

        if (! in_array($type, $allowedTypes, true)) {
            $value = is_array($value) ? '[' . implode(',', $value) . ']' : $value ?? 'null';
            $allowedTypes = collect($allowedTypes)->map(fn ($type) => $type ?? 'Null')->toArray();

            throw new ForestException("Wrong type for $field: $value. Expects " . implode(',', $allowedTypes));
        }
    }

    /**
     * @throws ForestException
     */
    public static function checkEnumValue(ColumnSchema $columnSchema, $enumValue): void
    {
        $isEnumAllowed = in_array($enumValue, $columnSchema->getEnumValues(), true);

        if (! $isEnumAllowed) {
            throw new ForestException(
                "The given enum value(s) $enumValue is not listed in [" . implode(',', $columnSchema->getEnumValues()) . "]",
            );
        }
    }
}
