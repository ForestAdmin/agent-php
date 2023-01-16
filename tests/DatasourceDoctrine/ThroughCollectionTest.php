<?php

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Types\IntegerType;
use ForestAdmin\AgentPHP\DatasourceDoctrine\DoctrineDatasource;
use ForestAdmin\AgentPHP\DatasourceDoctrine\ThroughCollection;
use Prophecy\Prophet;

beforeEach(closure: function () {
    global $metaData, $doctrineDatasource;

    $prophet = new Prophet();
    $doctrineDatasource = $prophet->prophesize(DoctrineDatasource::class);
    $doctrineDatasource = $doctrineDatasource->reveal();

    $metaData = [
        'name'               => 'foo_bar',
        'columns'            => [
            'foo_id' => new Column('foo_id', new IntegerType()),
            'bar_id' => new Column('bar_id', new IntegerType()),
        ],
        'foreignKeys'        => [
            'fk_123' => new ForeignKeyConstraint(['foo_id'], 'foo', ['id']),
            'fk_456' => new ForeignKeyConstraint(['bar_id'], 'bar', ['id']),
        ],
        'primaryKey'         => new Index('id', ['id']),
        'foreignCollections' => [
            'foo' => 'foo',
            'bar' => 'bar',
        ],
    ];
});

test('getIdentifier() should return the primary key name', function () {
    global $metaData, $doctrineDatasource;
    $collection = new ThroughCollection($doctrineDatasource, $metaData);

    expect($collection->getFields())->toHaveKeys(['foo_id', 'bar_id'])
        ->and($collection->getTableName())->toEqual('foo_bar');
});
