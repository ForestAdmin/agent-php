<?php

namespace ForestAdmin\AgentPHP\DatasourceEloquent\Utils;

use ForestAdmin\AgentPHP\BaseDatasource\Utils\DataTypes as BaseDataTypes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DataTypes extends BaseDataTypes
{
    /**
     * @var array
     * MorphTo not listed, we use only the field RELATION_id & RELATION_type
     * HasOneThrough & HasManyThrough not supported
     */
    public static array $eloquentRelationships = [
        BelongsTo::class      => 'BelongsTo',
        BelongsToMany::class  => 'BelongsToMany',
        HasMany::class        => 'HasMany',
        HasOne::class         => 'HasOne',
    ];
}
