<?php

namespace ForestAdmin\AgentPHP\Agent\Utils;

use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Schema as SchemaUtils;
use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\FieldValidator;

class Id
{
    public static function packId(CollectionContract $collection, array $record): string
    {
        $primaryKeyNames = SchemaUtils::getPrimaryKeys($collection);

        if (empty($primaryKeyNames)) {
            throw new ForestException('This collection has no primary key');
        }

        return collect($primaryKeyNames)->map(fn ($pk) => $record[$pk])->join('|');
    }

    public static function packIds(CollectionContract $collection, array $records): array
    {
        $values = collect($records)->map(fn ($item) => self::packId($collection, $item))->flatten();

        return $values->all();
    }

    public static function unpackId(CollectionContract $collection, string $packedId, bool $withKey = false): array
    {
        $primaryKeyNames = SchemaUtils::getPrimaryKeys($collection);
        $primaryKeyValues = explode('|', $packedId);

        if (count($primaryKeyNames) !== count($primaryKeyValues)) {
            throw new ForestException('Expected $primaryKeyNames a size of ' . count($primaryKeyNames) . ' values, found ' . count($primaryKeyValues));
        }

        $values = collect($primaryKeyNames)->mapWithKeys(function ($pkName, $index) use ($primaryKeyValues, $collection, $withKey) {
            $field = $collection->getFields()[$pkName];
            $value = $primaryKeyValues[$index];
            $castedValue = $field->getColumnType() === 'Number' ? (int) $value : $value;
            FieldValidator::validateValue($value, $field, $castedValue);

            return $withKey ? [$pkName => $value] : [$index => $value];
        });

        return $values->all();
    }

    public static function unpackIds(CollectionContract $collection, array $packedIds): array
    {
        $values = collect($packedIds)->map(fn ($item) => self::unpackId($collection, $item));

        return $values->all();
    }

    public static function parseSelectionIds(CollectionContract $collection, Request $request): array
    {
        $attributes = $request->input('data.attributes');
        $areExcluded = $attributes && array_key_exists('all_records', $attributes) ? $attributes['all_records'] : false;
        $inputIds = $attributes && array_key_exists('ids', $attributes)
            ? $attributes['ids']
            : collect($request->get('data'))->map(fn ($item) => $item['id'])->all();

        $ids = self::unpackIds($collection, $areExcluded ? $attributes['all_records_ids_excluded'] : $inputIds);

        return compact('areExcluded', 'ids');
    }
}
