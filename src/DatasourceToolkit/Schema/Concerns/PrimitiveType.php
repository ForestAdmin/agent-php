<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns;

final class PrimitiveType
{
    public const BOOLEAN = 'Boolean';
    public const DATE = 'Date';
    public const DATEONLY = 'Dateonly';
    public const ENUM = 'Enum';
    public const JSON = 'Json';
    public const NUMBER = 'Number';
    public const POINT = 'Point';
    public const STRING = 'String';
    public const TIMEONLY = 'Timeonly';
    public const UUID = 'Uuid';

    public static function tree(): array
    {
        return array_values(
            (new \ReflectionClass(self::class))->getConstants()
        );
    }
}
