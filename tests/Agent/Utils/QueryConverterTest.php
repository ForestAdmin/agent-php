<?php


use ForestAdmin\AgentPHP\Agent\Utils\QueryConverter;
use ForestAdmin\AgentPHP\BaseDatasource\BaseCollection;
use ForestAdmin\AgentPHP\BaseDatasource\BaseDatasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Page;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Sort;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use Illuminate\Database\Query\Builder;

$datasource = null;
$bookCollection = null;
$userCollection = null;
$reviewCollection = null;
$bookReviewCollection = null;
beforeEach(function () {
    global $datasource, $bookCollection, $reviewCollection, $bookReviewCollection, $userCollection;
    $datasource = new BaseDatasource(
        ['url' => 'sqlite://../../Datasets/test.db']
    );

    $bookCollection = new BaseCollection($datasource, 'Book', 'books');
    $bookCollection->addFields(
        [
            'id'            => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'title'         => new ColumnSchema(columnType: PrimitiveType::STRING),
            'published_at'  => new ColumnSchema(columnType: PrimitiveType::DATE),
            'author_id'     => new ColumnSchema(columnType: PrimitiveType::STRING),
            'author'        => new ManyToOneSchema(
                foreignKey: 'author_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'User',
            ),
            'reviews'       => new ManyToManySchema(
                originKey: 'book_id',
                originKeyTarget: 'id',
                throughTable: 'bookReview',
                foreignKey: 'review_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'Review',
                throughCollection: 'BookReview'
            ),
        ]
    );

    $userCollection = new BaseCollection($datasource, 'User', 'users');
    $userCollection->addFields(
        [
            'id'            => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'name'          => new ColumnSchema(columnType: PrimitiveType::STRING),
            'email'         => new ColumnSchema(columnType: PrimitiveType::STRING),
            'books'         => new OneToManySchema(
                originKey: 'id',
                originKeyTarget: 'author_id',
                foreignCollection: 'Book',
            ),
        ]
    );

    $reviewCollection = new BaseCollection($datasource, 'Review', 'reviews');
    $reviewCollection->addFields(
        [
            'id'            => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'author'        => new ColumnSchema(columnType: PrimitiveType::STRING),
            'rating'        => new ColumnSchema(columnType: PrimitiveType::NUMBER),
        ]
    );

    $bookReviewCollection = new BaseCollection($datasource, 'BookReview', 'book_review');
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

test('of() should return a Illuminate\\Database\\Query\\Builder instance', function () {
    global $bookCollection;
    expect(QueryConverter::of($bookCollection, 'Europe/Paris'))->toBeInstanceOf(Builder::class);
});

test('QueryConverter should select all when no projection is given', function () {
    global $bookCollection;
    $query = QueryConverter::of($bookCollection, 'Europe/Paris');

    expect($query->toSql())->toEqual('select * from "books" as "books"');
});

test('QueryConverter should select only fields from the given projection', function () {
    global $bookCollection;
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', null, new Projection(['id', 'title', 'published_at']));

    expect($query->columns[0]->getValue())->toEqual('"books"."id", "books"."title", "books"."published_at"');
});

test('QueryConverter should select only fields from the given projection and work with relations', function () {
    global $bookCollection;
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', null, new Projection(['id', 'title', 'reviews:author']));

    expect($query->columns[0]->getValue())
        ->toEqual('"books"."id", "books"."title", "reviews"."author" as "reviews.author"');
});

test('QueryConverter should add all the joins with ManyToMany relation', function () {
    global $bookCollection;
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', null, new Projection(['id', 'title', 'reviews:author']));

    expect($query->joins)->toHaveCount(2)
        ->and($query->joins[0]->table)->toEqual('book_review as book_review')
        ->and($query->joins[0]->wheres)->toHaveCount(1)
        ->and($query->joins[0]->wheres[0])->toEqual(
            [
                "type"     => "Column",
                "first"    => "books.id",
                "operator" => "=",
                "second"   => "book_review.book_id",
                "boolean"  => "and",
            ]
        )
        ->and($query->joins[1]->table)->toEqual('reviews as reviews')
        ->and($query->joins[1]->wheres)->toHaveCount(1)
        ->and($query->joins[1]->wheres[0])->toEqual(
            [
                "type"     => "Column",
                "first"    => "book_review.review_id",
                "operator" => "=",
                "second"   => "reviews.id",
                "boolean"  => "and",
            ]
        );
});

test('QueryConverter should add the join with ManyToOne relation', function () {
    global $bookReviewCollection;
    $query = QueryConverter::of($bookReviewCollection, 'Europe/Paris', null, new Projection(['id', 'review:author']));

    expect($query->joins)->toHaveCount(1)
        ->and($query->joins[0]->table)->toEqual('reviews as reviews')
        ->and($query->joins[0]->wheres)->toHaveCount(1)
        ->and($query->joins[0]->wheres[0])->toEqual(
            [
                "type"     => "Column",
                "first"    => "book_review.review_id",
                "operator" => "=",
                "second"   => "reviews.id",
                "boolean"  => "and",
            ]
        );
});

test('QueryConverter should add the join with OneToMany / OneToOne relation', function () {
    global $userCollection;
    $query = QueryConverter::of($userCollection, 'Europe/Paris', null, new Projection(['id', 'books:title']));

    expect($query->joins)->toHaveCount(1)
        ->and($query->joins[0]->table)->toEqual('books as books')
        ->and($query->joins[0]->wheres)->toHaveCount(1)
        ->and($query->joins[0]->wheres[0])->toEqual(
            [
                "type"     => "Column",
                "first"    => "users.id",
                "operator" => "=",
                "second"   => "books.author_id",
                "boolean"  => "and",
            ]
        );
});

test('QueryConverter should apply sort', function () {
    global $bookCollection;
    $filter = new PaginatedFilter(sort: new Sort([['field' => 'title', 'ascending' => false]]));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter);

    expect($query->orders)->toHaveCount(1)
        ->and($query->orders[0])->toEqual(
            [
                'column'    => 'books.title',
                'direction' => 'desc',
            ]
        );
});

test('QueryConverter should apply sort on relation', function () {
    global $bookCollection;
    $filter = new PaginatedFilter(sort: new Sort([['field' => 'author:name', 'ascending' => false]]));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'author:name']));

    expect($query->orders)->toHaveCount(1)
        ->and($query->orders[0]['column'])->toBeInstanceOf(Illuminate\Database\Query\Expression::class)
        ->and($query->orders[0]['column']->getValue())->toEqual('"author.name"')
        ->and($query->orders[0]['direction'])->toEqual('desc');
});

test('QueryConverter should apply pagination', function () {
    global $bookCollection;
    $filter = new PaginatedFilter(page: new Page(20, 10));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'author:name']));

    expect($query->limit)->toEqual(10)
        ->and($query->offset)->toEqual(20);
});
