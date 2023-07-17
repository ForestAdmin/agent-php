<?php

namespace ForestAdmin\AgentPHP\DatasourceDoctrine\Utils;

use ForestAdmin\AgentPHP\BaseDatasource\Utils\DataTypes as BaseDataTypes;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

class DataTypes extends BaseDataTypes
{
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
