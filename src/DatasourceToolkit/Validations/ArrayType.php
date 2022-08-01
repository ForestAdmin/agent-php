<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Validations;

use Spatie\Enum\Enum;

/**
 * @method static self Boolean()
 * @method static self Enum()
 * @method static self Number()
 * @method static self String()
 * @method static self Uuid()
 * @method static self Empty()
 * @method static self Null()
 */
final class ArrayType extends Enum
{
    protected static function values(): array
    {
        return [
            'Boolean' => 'ArrayOfBoolean',
            'Enum'    => 'ArrayOfEnum',
            'Number'  => 'ArrayOfNumber',
            'String'  => 'ArrayOfString',
            'Uuid'    => 'ArrayOfUuid',
            'Empty'   => 'EmptyArray',
        ];
    }

}
