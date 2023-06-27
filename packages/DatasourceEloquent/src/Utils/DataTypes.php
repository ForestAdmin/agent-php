<?php

namespace ForestAdmin\AgentPHP\DatasourceEloquent\Utils;

use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DataTypes
{
    /**
     * @var array
     * MorphTo not listed, we use only the field RELATION_id & RELATION_type
     * HasOneThrough & HasManyThrough not supported
     */
    public static array $eloquentRelationships = [
        BelongsTo::class      => 'BelongsTo',
        BelongsToMany::class  => 'BelongsToMany',
        HasMany::class        => 'HasMany',
        HasOne::class         => 'HasOne',
    ];

    public static function getType(string $type): string
    {
        return match ($type) {
            'binary', 'blob'                 => PrimitiveType::BINARY,
            'integer', 'float'               => PrimitiveType::NUMBER,
            'date'                           => PrimitiveType::DATEONLY,
            'datetime_immutable', 'datetime' => PrimitiveType::DATE,
            'boolean'                        => PrimitiveType::BOOLEAN,
            'time'                           => PrimitiveType::TIMEONLY,
            'json'                           => PrimitiveType::JSON,
            default                          => PrimitiveType::STRING,
        };
    }

    public static function renderValue(string $originalType, $content)
    {
        $type = self::getType($originalType);

        return match ($type) {
            PrimitiveType::DATEONLY => $content->format('Y-m-d'),
            PrimitiveType::DATE     => $content->format('Y-m-d H:i:s'),
            PrimitiveType::TIMEONLY => $content->format('H:i:s'),
            default                 => $content,
        };
    }
}
