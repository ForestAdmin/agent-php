<?php

use ForestAdmin\AgentPHP\BaseDatasource\BaseCollection;
use ForestAdmin\AgentPHP\BaseDatasource\BaseDatasource;
use ForestAdmin\AgentPHP\BaseDatasource\Utils\QueryAggregate;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\Tests\TestCase;

beforeEach(function () {
    global $datasource, $bookCollection, $reviewCollection, $bookReviewCollection, $userCollection;
    $this->initDatabase();
    $datasource = new BaseDatasource(TestCase::DB_CONFIG);

    $bookCollection = new BaseCollection($datasource, 'Book', 'books');
    $this->invokeProperty($bookCollection, 'fields', collect());
    $bookCollection = mock($bookCollection)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods()
        ->shouldReceive('fetchFieldsFromTable')
        ->andReturn(['columns' => [], 'primaries' => []])
        ->getMock();
    $bookCollection->addFields(
        [
            'id'           => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'title'        => new ColumnSchema(columnType: PrimitiveType::STRING),
            'published_at' => new ColumnSchema(columnType: PrimitiveType::DATE),
            'author_id'    => new ColumnSchema(columnType: PrimitiveType::STRING),
            'author'       => new ManyToOneSchema(
                foreignKey: 'author_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'User',
            ),
            'reviews'      => new ManyToManySchema(
                originKey: 'book_id',
                originKeyTarget: 'id',
                foreignKey: 'review_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'Review',
                throughCollection: 'BookReview'
            ),
        ]
    );

    $userCollection = new BaseCollection($datasource, 'User', 'users');
    $this->invokeProperty($userCollection, 'fields', collect());
    $userCollection = mock($userCollection)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods()
        ->shouldReceive('fetchFieldsFromTable')
        ->andReturn(['columns' => [], 'primaries' => []])
        ->getMock();
    $userCollection->addFields(
        [
            'id'    => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'name'  => new ColumnSchema(columnType: PrimitiveType::STRING),
            'email' => new ColumnSchema(columnType: PrimitiveType::STRING),
            'books' => new OneToManySchema(
                originKey: 'id',
                originKeyTarget: 'author_id',
                foreignCollection: 'Book',
            ),
        ]
    );

    $reviewCollection = new BaseCollection($datasource, 'Review', 'reviews');
    $this->invokeProperty($reviewCollection, 'fields', collect());
    $reviewCollection = mock($reviewCollection)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods()
        ->shouldReceive('fetchFieldsFromTable')
        ->andReturn(['columns' => [], 'primaries' => []])
        ->getMock();
    $reviewCollection->addFields(
        [
            'id'     => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'author' => new ColumnSchema(columnType: PrimitiveType::STRING),
            'rating' => new ColumnSchema(columnType: PrimitiveType::NUMBER),
        ]
    );

    $bookReviewCollection = new BaseCollection($datasource, 'BookReview', 'book_review');
    $this->invokeProperty($bookReviewCollection, 'fields', collect());
    $bookReviewCollection = mock($bookReviewCollection)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods()
        ->shouldReceive('fetchFieldsFromTable')
        ->andReturn(['columns' => [], 'primaries' => []])
        ->getMock();
    $bookReviewCollection->addFields(
        [
            'id'        => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'review_id' => new ColumnSchema(columnType: PrimitiveType::NUMBER),
            'review'    => new ManyToOneSchema(
                foreignKey: 'review_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'Review',
            ),
            'book_id'   => new ColumnSchema(columnType: PrimitiveType::NUMBER),
            'book'      => new ManyToOneSchema(
                foreignKey: 'book_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'Book',
            ),
        ]
    );

    $datasource->addCollection($bookCollection);
    $datasource->addCollection($userCollection);
    $datasource->addCollection($reviewCollection);
    $datasource->addCollection($bookReviewCollection);
});

test('of() should return a ForestAdmin\\AgentPHP\\Agent\\Utils\\QueryAggregate instance', function () {
    global $bookCollection;
    $query = QueryAggregate::of($bookCollection, 'Europe/Paris', new Aggregation('Count'));

    expect($query)->toBeInstanceOf(QueryAggregate::class);
});

test('get() should return a array of array with key value and group', function () {
    global $bookCollection;
    $query = QueryAggregate::of($bookCollection, 'Europe/Paris', new Aggregation('Count'));

    expect($query->get())->toEqual([['value' => 4, 'group' => []]]);
});

test('get() should work with grouped aggregation', function () {
    global $bookCollection;
    $aggregation = new Aggregation('Sum', 'price', [['field' => 'author_id']]);
    $query = QueryAggregate::of($bookCollection, 'Europe/Paris', $aggregation);

    expect($query->get())->toEqual(
        [
            ['value' => 20, 'group' => ['author_id' => 1]],
            ['value' => 10, 'group' => ['author_id' => 3]],
            ['value' => 10, 'group' => ['author_id' => 2]],
        ]
    );
});
