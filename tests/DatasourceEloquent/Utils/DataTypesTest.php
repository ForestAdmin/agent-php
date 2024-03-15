<?php

use ForestAdmin\AgentPHP\DatasourceEloquent\Utils\DataTypes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

describe('test describe', function () {
    test('get const eloquentRelationships', function () {
        expect(DataTypes::$eloquentRelationships)->toEqual(
            [
                BelongsTo::class      => 'BelongsTo',
                BelongsToMany::class  => 'BelongsToMany',
                HasMany::class        => 'HasMany',
                HasOne::class         => 'HasOne',
            ]
        );
    });
});
