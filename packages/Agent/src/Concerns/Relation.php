<?php

namespace ForestAdmin\AgentPHP\Agent\Concerns;

final class Relation
{
    protected static function values(): array
    {
        return [
            'BelongsToMany' => 'ManyToMany',
            'BelongsTo'     => 'ManyToOne',
            'HasMany'       => 'OneToMany',
            'HasOne'        => 'OneToOne',
        ];
    }

    public static function getRelation(string $key): string
    {
        return array_flip(self::values())[$key];
    }
}
