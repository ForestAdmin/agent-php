<?php

use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use ForestAdmin\AgentPHP\DatasourceDoctrine\DoctrineDatasource;
use ForestAdmin\AgentPHP\DatasourceDoctrine\ThroughCollection;
use ForestAdmin\AgentPHP\Tests\TestCase;

beforeEach(closure: function () {
    global $metaData, $doctrineDatasource;

    $this->initDatabase();
    $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__ . '/Entity'], false);
    $entityManager = new EntityManager($this->getDoctrineConnection(), $config);
    $doctrineDatasource = new DoctrineDatasource($entityManager, TestCase::DB_CONFIG);

    $metaData = [
        'name'               => 'car_owner',
        'foreignKeys'        => [
            'fk_123' => new ForeignKeyConstraint(['car_id'], 'cars', ['id']),
            'fk_456' => new ForeignKeyConstraint(['owner_id'], 'owners', ['id']),
        ],
        'foreignCollections' => [
            'cars'   => 'Car',
            'owners' => 'Owner',
        ],
    ];
});

test('getIdentifier() should return the primary key name', function () {
    global $metaData, $doctrineDatasource;
    $collection = new ThroughCollection($doctrineDatasource, $metaData);

    expect($collection->getFields())->toHaveKeys(['car_id', 'owner_id'])
        ->and($collection->getTableName())->toEqual('car_owner');
});
