<?php

use ForestAdmin\AgentPHP\DatasourceEloquent\EloquentCollection;
use ForestAdmin\AgentPHP\DatasourceEloquent\EloquentDatasource;
use ForestAdmin\AgentPHP\DatasourceEloquent\ThroughCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;
use ForestAdmin\AgentPHP\Tests\TestCase;

use function Ozzie\Nest\describe;
use function Ozzie\Nest\test;

beforeEach(closure: function () {
    global $eloquentDatasource;
    $this->buildAgent(new Datasource(), ['projectDir' => __DIR__]);
    $this->initDatabase();
    $eloquentDatasource = new EloquentDatasource(TestCase::DB_CONFIG);
});


describe('addRelationships()', function () {
    test('should add OneToManySchema field when relation is a HasMany', function () {
        /** @var EloquentDatasource $eloquentDatasource */
        global $eloquentDatasource;
        /** @var EloquentCollection $collection */
        $collection = $eloquentDatasource->getCollection('Author');

        expect($collection->getFields())->toHaveKey('books')
            ->and($collection->getFields()['books'])->toBeInstanceOf(OneToManySchema::class);
    });

    test('should add ManyToOneSchema field when relation is a BelongsTo with an inverse HasMany', function () {
        /** @var EloquentDatasource $eloquentDatasource */
        global $eloquentDatasource;
        /** @var EloquentCollection $collection */
        $collection = $eloquentDatasource->getCollection('Book');

        expect($collection->getFields())->toHaveKey('author')
            ->and($collection->getFields()['author'])->toBeInstanceOf(ManyToOneSchema::class);
    });

    test('should add OneToOneSchema field when relation is a BelongsTo with an inverse HasOne', function () {
        /** @var EloquentDatasource $eloquentDatasource */
        global $eloquentDatasource;
        /** @var EloquentCollection $collection */
        $collection = $eloquentDatasource->getCollection('Owner');

        expect($collection->getFields())->toHaveKey('user')
            ->and($collection->getFields()['user'])->toBeInstanceOf(OneToOneSchema::class);
    });

    test('should add OneToOneSchema field when relation is a HasOne', function () {
        /** @var EloquentDatasource $eloquentDatasource */
        global $eloquentDatasource;
        /** @var EloquentCollection $collection */
        $collection = $eloquentDatasource->getCollection('User');

        expect($collection->getFields())->toHaveKey('owner')
            ->and($collection->getFields()['owner'])->toBeInstanceOf(OneToOneSchema::class);
    });

    test('should add ManyToManySchema field when relation is a BelongsToMany', function () {
        /** @var EloquentDatasource $eloquentDatasource */
        global $eloquentDatasource;
        /** @var EloquentCollection $collection */
        $collection = $eloquentDatasource->getCollection('Book');

        expect($collection->getFields())->toHaveKey('reviews')
            ->and($collection->getFields()['reviews'])->toBeInstanceOf(ManyToManySchema::class);
    });

    test('should add a throughCollection to the datasource when the throughCollection doesn\'t exist', function () {
        /** @var EloquentDatasource $eloquentDatasource */
        global $eloquentDatasource;
        $collections = $eloquentDatasource->getCollections();

        expect($collections)->toHaveKey('CarOwner')
            ->and($collections->get('CarOwner'))->toBeInstanceOf(ThroughCollection::class);
    });
});
