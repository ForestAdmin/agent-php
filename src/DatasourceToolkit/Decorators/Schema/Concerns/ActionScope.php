<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns;

use Spatie\Enum\Enum;

/**
 * @method static self single()
 * @method static self bulk()
 * @method static self global()
 */
final class ActionScope extends Enum
{
}