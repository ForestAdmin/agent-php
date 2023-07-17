<?php

use ForestAdmin\AgentPHP\DatasourceEloquent\EloquentDatasource;
use ForestAdmin\AgentPHP\DatasourceEloquent\ThroughCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\Tests\DatasourceDoctrine\Entity\Car;
use ForestAdmin\AgentPHP\Tests\DatasourceDoctrine\Entity\Owner;
use ForestAdmin\AgentPHP\Tests\TestCase;

use function Ozzie\Nest\describe;
use function Ozzie\Nest\test;

beforeEach(closure: function () {
    global $eloquentDatasource;
    $this->buildAgent(new Datasource(), ['projectDir' => __DIR__]);
    $this->initDatabase();
    $eloquentDatasource = new EloquentDatasource(TestCase::DB_CONFIG);
});

describe('on build ThroughCollection', function () {
    test('should return the relation name between 2 collections', function () {
        global $eloquentDatasource;
        $throughCollection = new ThroughCollection(
            $eloquentDatasource,
            [
                'name'               => 'cars',
                'tableName'          => 'car_owner',
                'relations'          => [
                    [
                        'foreignKey'        => 'car_id',
                        'foreignKeyTarget'  => 'id',
                        'foreignCollection' => 'Car',
                    ],
                    [
                        'foreignKey'        => 'owner_id',
                        'foreignKeyTarget'  => 'id',
                        'foreignCollection' => 'Owner',
                    ],
                ],
                'foreignCollections' => [
                    'cars'     => Car::class,
                    'owners'   => Owner::class,
                ],
            ]
        );

        expect($throughCollection->getFields())->toHaveKeys(['car', 'owner']);
        expect($throughCollection->getFields()['car'])->toBeInstanceOf(ManyToOneSchema::class);
        expect($throughCollection->getFields()['owner'])->toBeInstanceOf(ManyToOneSchema::class);
    });
});
