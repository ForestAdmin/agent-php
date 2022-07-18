<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Validations;

use Exception;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;
use Illuminate\Support\Str;
use function ForestAdmin\cache;

class FieldValidator
{
    /**
     * @throws Exception
     */
    public static function validate(Collection $collection, string $field, $values = null)
    {
        if (! Str::contains($field, ':')) {
            $column = $collection->getFields()->get($field);
            if (! $column) {
                throw new Exception('Column not found: ' . $collection->getName() . '.' . $field);
            }

            if ($column->getType() !== 'Column') {
                throw new Exception('Unexpected field type: ' .
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
                throw new Exception('Relation not found: ' . $collection->getName() . '.' . $prefix);
            }

            if ($relation->getType() !== 'ManyToOne' && $relation->getType() !== 'OneToOne') {
                throw new Exception('Unexpected field type: ' .
                    $collection->getName() . '.' . $prefix .
                    ' (found ' . $relation->getType() . ' expected \'ManyToOne\' or \'OneToOne\')'
                );
            }

            $suffix = Str::after($field, ':');
            $association = cache('datasource')->getCollection($relation->getForeignCollection());
            self::validate($association, $suffix, $values);
        }
    }

    /**
     * @throws Exception
     */
    public static function validateValue(string $field, ColumnSchema $columnSchema, $value, array $allowedTypes): void
    {
        // FIXME: handle complex type from ColumnType
        if (gettype($columnSchema->getColumnType()) !== 'string') {
        }

        $type = TypeGetter::get($value, $columnSchema->getColumnType());

        if ($columnSchema->getColumnType() === PrimitiveType::ENUM) {
            self::checkEnumValue($type, $columnSchema, $value);
        }

        if ($allowedTypes && !in_array($type, $allowedTypes, true)) {
            throw new Exception("Wrong type for $field: $value. Expects " . implode(',', $allowedTypes));
        } elseif ($type !== $columnSchema->getColumnType()) {
            throw new Exception("Wrong type for $field: $value. Expects " . $columnSchema->getColumnType());
        }
    }

    /**
     * @throws Exception
     */
    public static function checkEnumValue(string $type, ColumnSchema $columnSchema, $enumValue): void
    {
        $isEnumAllowed = false;

        if ($type === ValidationType::Enum()->value) {
            $enumValuesConditionTree = collect($enumValue);
            $isEnumAllowed = $enumValuesConditionTree->every(
                fn ($value) => in_array($value, $columnSchema->getEnumValues(), true)
            );
        } else {
            $isEnumAllowed = in_array($enumValue, $columnSchema->getEnumValues(), true);
        }

        if (! $isEnumAllowed) {
            throw new Exception(
                "The given enum value(s) [$enumValue] is not listed in [". implode(',', $columnSchema->getEnumValues()) ."]",
            );
        }
    }
}
