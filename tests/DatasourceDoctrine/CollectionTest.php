<?php

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use ForestAdmin\AgentPHP\DatasourceDoctrine\DoctrineDatasource;
use ForestAdmin\AgentPHP\Tests\DatasourceDoctrine\Entity\Book;
use ForestAdmin\AgentPHP\Tests\TestCase;

beforeEach(closure: function () {
    global $doctrineDatasource;
    $this->initDatabase();
    $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__ . '/Entity'], false);
    $entityManager = new EntityManager($this->getConnection()->getDoctrineConnection(), $config);
    $doctrineDatasource = new DoctrineDatasource($entityManager, TestCase::DB_CONFIG);
});

test('getIdentifier() should return the primary key name', function () {
    /** @var DoctrineDatasource $doctrineDatasource */
    global $doctrineDatasource;
    $collection = $doctrineDatasource->getCollection('Book');

    expect($collection->getIdentifier())->toEqual('id');
});

test('getClassName() should return the entity className', function () {
    /** @var DoctrineDatasource $doctrineDatasource */
    global $doctrineDatasource;
    $collection = $doctrineDatasource->getCollection('Book');

    expect($collection->getClassName())->toEqual(Book::class);
});

test('getRelationType() should return a null type on unknown relation', function () {
    /** @var DoctrineDatasource $doctrineDatasource */
    global $doctrineDatasource;

    $reflectionClass = new ReflectionClass(Book::class);
    // fake relation attribute declared in Book entity
    $authorFakeAttributes = $reflectionClass->getProperty('authorFake')->getAttributes();
    $collection = $doctrineDatasource->getCollection('Book');
    $result = $this->invokeMethod($collection, 'getRelationType', [$authorFakeAttributes]);

    expect($result)->toBeNull();
});

//test('addOneToOne() should throw when the mapped field doesn\'t exist into the related entity', function () {
//    /** @var DoctrineDatasource $doctrineDatasource */
//    global $doctrineDatasource;
//    $classMetadata->associationMappings = [
//        'driverLicence' => [
//            'fieldName'    => 'driverLicence',
//            'targetEntity' => (new \ReflectionClass($entityDriverLicence))->getName(),
//            'joinColumns'  => [],
//            'mappedBy'     => 'my-related-field',
//        ],
//    ];
//
//    expect(fn () => new Collection($doctrineDatasource, $classMetadata))->toThrow(\Exception::class, 'The relation field `my-related-field` does not exist in the entity `'.(new \ReflectionClass($entityDriverLicence))->getName().'`.');
//});
//
//test('addManyToMany() should throw when the mapped field doesn\'t exist into the related entity', function () {
//    /** @var DoctrineDatasource $doctrineDatasource */
//    global $doctrineDatasource;
//    $classMetadata->associationMappings = [
//        'users'         => [
//            'fieldName'    => 'users',
//            'joinTable'    => [],
//            'targetEntity' => (new \ReflectionClass($entityUser))->getName(),
//            'mappedBy'     => 'my-related-field',
//            'inversedBy'   => null,
//        ],
//    ];
//
//    expect(fn () => new Collection($doctrineDatasource, $classMetadata))->toThrow(\Exception::class, 'The relation field `my-related-field` does not exist in the entity `'.(new \ReflectionClass($entityUser))->getName().'`.');
//});
//
//test('addOneToMany() should throw when the mapped field doesn\'t exist into the related entity', function () {
//    /** @var DoctrineDatasource $doctrineDatasource */
//    global $doctrineDatasource;
//    $classMetadata->associationMappings = [
//        'books'         => [
//            'fieldName'    => 'books',
//            'targetEntity' => (new \ReflectionClass($entityBook))->getName(),
//            'mappedBy'     => 'my-related-field',
//        ],
//    ];
//
//    expect(fn () => new Collection($doctrineDatasource, $classMetadata))->toThrow(\Exception::class, 'The relation field `my-related-field` does not exist in the entity `'.(new \ReflectionClass($entityBook))->getName().'`.');
//});
