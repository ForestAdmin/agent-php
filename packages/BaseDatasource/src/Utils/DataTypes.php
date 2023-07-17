<?php

namespace ForestAdmin\AgentPHP\BaseDatasource\Utils;

use Doctrine\DBAL\Types\Types;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

class DataTypes
{
    public static function getType(string $type): string
    {
        return match ($type) {
            Types::BIGINT, Types::DECIMAL, Types::FLOAT, Types::INTEGER, Types::SMALLINT => PrimitiveType::NUMBER,
            Types::BOOLEAN                                                               => PrimitiveType::BOOLEAN,
            Types::DATE_MUTABLE, Types::DATE_IMMUTABLE                                   => PrimitiveType::DATEONLY,
            Types::GUID                                                                  => 'Uuid',
            Types::TIME_MUTABLE, Types::TIME_IMMUTABLE                                   => PrimitiveType::TIMEONLY,
            Types::DATETIME_MUTABLE,
            Types::DATETIME_IMMUTABLE,
            Types::DATETIMETZ_MUTABLE,
            Types::DATETIMETZ_IMMUTABLE,
            'timestamp',                                                                 => PrimitiveType::DATE,
            'json'                                                                       => PrimitiveType::JSON,
            default                                                                      => PrimitiveType::STRING,
        };
    }
}
