<?php

namespace ForestAdmin\AgentPHP\Agent\Concerns;

use Spatie\Enum\Enum;

/**
 * @method static self ManyToMany()
 * @method static self ManyToOne()
 * @method static self OneToMany()
 * @method static self OneToOne()
 */
final class Relation extends Enum
{
    protected static function values(): array
    {
        return [
            'ManyToMany' => 'BelongsToMany',
            'ManyToOne'  => 'BelongsTo',
            'OneToMany'  => 'OneToMany',
            'OneToOne'   => 'OneToOne',
        ];
    }

    public static function getRelation(string $key): string
    {
        return array_flip(self::toArray())[$key];
    }

}
