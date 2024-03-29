<?php

use ForestAdmin\AgentPHP\Agent\Concerns\Relation;

test('getRelation() should return the correct value of the key provided', function () {
    expect(Relation::getRelation('ManyToMany'))
        ->toEqual('BelongsToMany')
        ->and(Relation::getRelation('ManyToOne'))
        ->toEqual('BelongsTo')
        ->and(Relation::getRelation('OneToMany'))
        ->toEqual('HasMany')
        ->and(Relation::getRelation('OneToOne'))
        ->toEqual('HasOne');
});
