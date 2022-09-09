<?php

namespace ForestAdmin\AgentPHP\Agent\Utils;

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Schema as SchemaUtils;
use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\FieldValidator;

class Id
{
    public static function unpackId(Collection $collection, string $packedId): array
    {
        $primaryKeyNames = SchemaUtils::getPrimaryKeys($collection);
        $primaryKeyValues = explode('|', $packedId);

        if (count($primaryKeyNames) !== count($primaryKeyValues)) {
            throw new ForestException('Expected $primaryKeyNames a size of ' . count($primaryKeyNames) . ' values, found ' . count($primaryKeyValues));
        }

        $values = collect($primaryKeyNames)->mapWithKeys(function ($pkName, $index) use ($primaryKeyValues, $collection) {
            $field = $collection->getFields()[$pkName];
            $value = $primaryKeyValues[$index];
            $castedValue = $field->getColumnType() === 'Number' ? (int) $value : $value;
            FieldValidator::validateValue($value, $field, $castedValue);

            return [$value];
        });

        return $values->all();
    }

    public static function unpackIds(Collection $collection, array $packedIds): array
    {
        $values = collect($packedIds)->map(fn ($item) => self::unpackId($collection, $item))->flatten();

        return $values->all();
    }
}
