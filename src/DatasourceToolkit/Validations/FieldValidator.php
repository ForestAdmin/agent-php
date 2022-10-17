<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Validations;

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use Illuminate\Support\Str;

class FieldValidator
{
    /**
     * @throws ForestException
     */
    public static function validate(Collection $collection, string $field, $values = null)
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
            $relation = $collection->getFields()->get($prefix);

            if (! $relation) {
                throw new ForestException('Relation not found: ' . $collection->getName() . '.' . $prefix);
            }

            if ($relation->getType() !== 'ManyToOne' && $relation->getType() !== 'OneToOne') {
                throw new ForestException(
                    'Unexpected field type: ' .
                    $collection->getName() . '.' . $prefix .
                    ' (found ' . $relation->getType() . ' expected \'ManyToOne\' or \'OneToOne\')'
                );
            }

            $suffix = Str::after($field, ':');
            $association = AgentFactory::get('datasource')->getCollection($relation->getForeignCollection());
            self::validate($association, $suffix, $values);
        }
    }

    /**
     * @throws Exception
     */
    public static function validateValue(string $field, ColumnSchema $columnSchema, $value, ?array $allowedTypes = null): void
    {
        // FIXME: handle complex type from ColumnType
        if (gettype($columnSchema->getColumnType()) !== PrimitiveType::STRING) {
        }

        $type = TypeGetter::get($value, $columnSchema->getColumnType());
//        dd($type, $value,  $columnSchema->getColumnType());
        if ($columnSchema->getColumnType() === PrimitiveType::ENUM) {
            self::checkEnumValue($type, $columnSchema, $value);
        }

        $value = is_array($value) ? '[' . implode(',', $value) . ']' : $value;
        if ($allowedTypes && ! in_array($type, $allowedTypes, true)) {
            throw new ForestException("Wrong type for $field: $value. Expects " . implode(',', $allowedTypes));
        }

        if ($type instanceof ArrayType) {
            $type = $type->label;
        }

        if (! $allowedTypes && $type !== $columnSchema->getColumnType()) {
            throw new ForestException("Wrong type for $field: $value. Expects " . $columnSchema->getColumnType());
        }
    }

    /**
     * @throws ForestException
     */
    public static function checkEnumValue(string $type, ColumnSchema $columnSchema, $enumValue): void
    {
        if ($type === ArrayType::Enum()->value) {
            $enumValuesConditionTree = collect($enumValue);
            $isEnumAllowed = $enumValuesConditionTree->every(
                fn ($value) => in_array($value, $columnSchema->getEnumValues(), true)
            );
        } else {
            $isEnumAllowed = in_array($enumValue, $columnSchema->getEnumValues(), true);
        }
        $enumValue = is_array($enumValue) ? '[' . implode(',', $enumValue) . ']' : $enumValue;

        if (! $isEnumAllowed) {
            throw new ForestException(
                "The given enum value(s) $enumValue is not listed in [" . implode(',', $columnSchema->getEnumValues()) . "]",
            );
        }
    }
}
