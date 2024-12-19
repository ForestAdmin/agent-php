<?php

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use ForestAdmin\AgentPHP\DatasourceDoctrine\DoctrineDatasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\Tests\TestCase;
use Illuminate\Support\Collection;
use Symfony\Bridge\Doctrine\ManagerRegistry;

describe('When manager is a EntityManager object', function () {
    beforeEach(closure: function () {
        global $entityManager;
        $this->initDatabase();
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__ . '/Entity'], false);
        $entityManager = new EntityManager($this->getDoctrineConnection(), $config);
    });

    it('can instantiate DoctrineDatasource with valid ManagerRegistry', function () {
        global $entityManager;
        $datasource = new DoctrineDatasource($entityManager, TestCase::DB_CONFIG);

        expect($datasource)->toBeInstanceOf(DoctrineDatasource::class)
            ->and($datasource->getEntityManager())->toBeInstanceOf(EntityManager::class);
    });

    it('throws exception for unknown native query connection', function () {
        global $entityManager;

        $datasource = new DoctrineDatasource($entityManager, TestCase::DB_CONFIG);
        $datasource->executeNativeQuery('unknown_connection', 'SELECT * FROM users');
    })->throws(ForestException::class, "Native query connection 'unknown_connection' is unknown.");
});

describe('When manager is a Doctrine object', function () {
    it('generates collections from ManagerRegistry', function () {
        $this->initDatabase();
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__ . '/Entity'], false);

        $entityManager = new EntityManager($this->getDoctrineConnection(), $config);
        $mockManagerRegistry = Mockery::mock(ManagerRegistry::class);
        $mockManagerRegistry->shouldReceive('getManager')->andReturn($entityManager);
        $mockManagerRegistry->shouldReceive('getDefaultConnectionName')->andReturn('default');

        $datasource = new DoctrineDatasource($mockManagerRegistry, TestCase::DB_CONFIG);

        expect($datasource->getEntityManager())->toBe($entityManager)
            ->and($datasource->getCollections())->toBeInstanceOf(Collection::class)
            ->and($datasource->getCollections())->toHaveKey('Book');
    });

    it('serializes and unserializes correctly', function () {
        $this->initDatabase();
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__ . '/Entity'], false);

        $entityManager = new EntityManager($this->getDoctrineConnection(), $config);
        $mockManagerRegistry = Mockery::mock(ManagerRegistry::class);
        $mockManagerRegistry->shouldReceive('getManager')->andReturn($entityManager);
        $mockManagerRegistry->shouldReceive('getDefaultConnectionName')->andReturn('default');

        $datasource = new DoctrineDatasource($mockManagerRegistry, TestCase::DB_CONFIG, ['default' => 'default']);
        $serialized = serialize($datasource);
        $unserializedDatasource = unserialize($serialized);

        expect($unserializedDatasource->getLiveQueryConnections())->toBe(['default' => 'default']);
    });
});
