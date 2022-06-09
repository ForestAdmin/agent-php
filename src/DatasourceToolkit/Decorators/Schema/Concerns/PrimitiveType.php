<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns;

use Spatie\Enum\Enum;

/**
 * @method static self Boolean()
 * @method static self Date()
 * @method static self Dateonly()
 * @method static self Enum()
 * @method static self Json()
 * @method static self Number()
 * @method static self Point()
 * @method static self String()
 * @method static self Timeonly()
 * @method static self Uuid()
 */
final class PrimitiveType extends Enum
{
}
