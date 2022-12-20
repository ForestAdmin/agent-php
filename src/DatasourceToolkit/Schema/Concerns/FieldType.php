<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns;

use Spatie\Enum\Enum;

/**
 * @method static self Column()
 * @method static self OneToOne()
 * @method static self OneToMany()
 * @method static self ManyToOne()
 * @method static self ManyToMany()
 */
final class FieldType extends Enum
{
}
