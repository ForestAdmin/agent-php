<?php

namespace ForestAdmin\AgentPHP\DatasourceDoctrine\Utils;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Schema\Concerns\PrimitiveType;

class DataTypes
{
    public static function getType(string $type): string
    {
        return match ($type) {
            'integer', 'float'               => PrimitiveType::NUMBER,
            'date'                           => PrimitiveType::DATEONLY,
            'datetime_immutable', 'datetime' => PrimitiveType::DATE,
            'boolean'                        => PrimitiveType::BOOLEAN,
            'time'                           => PrimitiveType::TIMEONLY,
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
