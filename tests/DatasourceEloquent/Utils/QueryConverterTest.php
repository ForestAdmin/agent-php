<?php

use ForestAdmin\AgentPHP\DatasourceEloquent\EloquentDatasource;
use ForestAdmin\AgentPHP\DatasourceEloquent\Utils\QueryConverter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Page;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Sort;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\Tests\TestCase;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

const ELOQUENT_TIMEZONE = 'Europe/Paris';

beforeEach(function () {
    testTime()->freeze(Carbon::now(ELOQUENT_TIMEZONE));
    global $datasource, $bookCollection, $reviewCollection, $bookReviewCollection, $authorCollection, $commentCollection;
    $this->buildAgent(new Datasource(), ['projectDir' => str_replace('/Utils', '', __DIR__)]);
    $this->initDatabase();
    $datasource = new EloquentDatasource(TestCase::DB_CONFIG, 'eloquent_collection', true);

    $bookCollection = $datasource->getCollection('ForestAdmin_AgentPHP_Tests_DatasourceEloquent_Models_Book');
    $authorCollection = $datasource->getCollection('ForestAdmin_AgentPHP_Tests_DatasourceEloquent_Models_Author');
    $reviewCollection = $datasource->getCollection('ForestAdmin_AgentPHP_Tests_DatasourceEloquent_Models_Review');
    $bookReviewCollection = $datasource->getCollection('ForestAdmin_AgentPHP_Tests_DatasourceEloquent_Models_BookReview');
    $commentCollection = $datasource->getCollection('ForestAdmin_AgentPHP_Tests_DatasourceEloquent_Models_Comment');
});

test('of() should return a ForestAdmin\\AgentPHP\\DatasourceEloquent\\Utils\\QueryConverter instance', function () {
    global $bookCollection;
    expect(QueryConverter::of($bookCollection, 'Europe/Paris'))->toBeInstanceOf(QueryConverter::class);
});

test('QueryConverter should select all when no projection is given', function () {
    global $bookCollection;
    $query = QueryConverter::of($bookCollection, 'Europe/Paris')->getQuery();

    expect($query->toSql())->toEqual('select * from "books"');
});

test('QueryConverter should select only fields from the given projection', function () {
    global $bookCollection;
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', null, new Projection(['id', 'title', 'published_at']))
        ->getQuery();

    expect($query->getQuery()->columns)
        ->toEqual(
            [
                'books.id',
                'books.title',
                'books.published_at',
            ]
        );
});

test('QueryConverter should select only fields from the given projection and work with relations', function () {
    global $bookCollection;
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', null, new Projection(['id', 'title', 'reviews:author']))
        ->getQuery();

    expect($query->getQuery()->columns)
        ->toEqual(
            [
                'books.id',
                'books.title',
                'reviews.author as reviews.author',
            ]
        );
});

test('QueryConverter should add all the joins with ManyToMany relation', function () {
    global $bookCollection;
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', null, new Projection(['id', 'title', 'reviews:author']))
        ->getQuery()->getQuery();

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
    $query = QueryConverter::of($bookReviewCollection, 'Europe/Paris', null, new Projection(['id', 'review:author']))
        ->getQuery()->getQuery();

    expect($query->joins)->toHaveCount(1)
        ->and($query->joins[0]->table)->toEqual('reviews as review')
        ->and($query->joins[0]->wheres)->toHaveCount(1)
        ->and($query->joins[0]->wheres[0])->toEqual(
            [
                "type"     => "Column",
                "first"    => "book_review.review_id",
                "operator" => "=",
                "second"   => "review.id",
                "boolean"  => "and",
            ]
        );
});

test('QueryConverter should add the join with OneToMany / OneToOne relation', function () {
    global $bookCollection;
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', null, new Projection(['id', 'author:email']))
        ->getQuery()->getQuery();

    expect($query->joins)->toHaveCount(1)
        ->and($query->joins[0]->table)->toEqual('authors as author')
        ->and($query->joins[0]->wheres)->toHaveCount(1)
        ->and($query->joins[0]->wheres[0])->toEqual(
            [
                "type"     => "Column",
                "first"    => "books.author_id",
                "operator" => "=",
                "second"   => "author.id",
                "boolean"  => "and",
            ]
        );
});

test('QueryConverter should add the join PolymorphicManyToOne relation', function () {
    global $commentCollection;
    $query = QueryConverter::of($commentCollection, 'Europe/Paris', null, new Projection(['id', 'commentable:book']))
        ->getQuery()->getQuery();

    expect($query->joins)->toHaveCount(2)
        ->and($query->joins[0]->table)->toEqual('books as polymorphic_commentable_ForestAdmin_AgentPHP_Tests_DatasourceEloquent_Models_Book')
        ->and($query->joins[0]->wheres)->toHaveCount(2)
        ->and($query->joins[0]->wheres)->toEqual([
            [
                "type"     => "Column",
                "first"    => "comments.commentable_id",
                "operator" => "=",
                "second"   => "polymorphic_commentable_ForestAdmin_AgentPHP_Tests_DatasourceEloquent_Models_Book.id",
                "boolean"  => "and",
            ],
            [
                "type"     => "Basic",
                "operator" => "=",
                "boolean"  => "and",
                "column"   => "comments.commentable_type",
                "value"    => "ForestAdmin\AgentPHP\Tests\DatasourceEloquent\Models\Book",
            ],
        ]);
});

test('QueryConverter should apply sort', function () {
    global $bookCollection;
    $filter = new PaginatedFilter(sort: new Sort([['field' => 'title', 'ascending' => false]]));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter)->getQuery()->getQuery();

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
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'author:name']))
        ->getQuery()->getQuery();

    expect($query->orders)->toHaveCount(1)
        ->and($query->orders[0]['column'])->toEqual('author.name')
        ->and($query->orders[0]['direction'])->toEqual('desc');
});

test('QueryConverter should apply pagination', function () {
    global $bookCollection;
    $filter = new PaginatedFilter(page: new Page(20, 10));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'author:name']))
        ->getQuery()->getQuery();

    expect($query->limit)->toEqual(10)
        ->and($query->offset)->toEqual(20);
});

test('QueryConverter apply conditionTree should add join with nested field', function () {
    global $bookCollection;
    $filter = new Filter(new ConditionTreeLeaf('author:name', Operators::PRESENT));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'title']))
        ->getQuery()->getQuery();

    expect($query->joins)
        ->toHaveCount(1)
        ->and($query->joins[0])
        ->toBeInstanceOf(JoinClause::class)
        ->and($query->joins[0]->table)
        ->toEqual('authors as author')
        ->and($query->joins[0]->type)
        ->toEqual('left')
        ->and($query->joins[0]->wheres)
        ->toHaveCount(1)
        ->and($query->joins[0]->wheres[0])
        ->toEqual([
            'type'     => 'Column',
            'first'    => 'books.author_id',
            'operator' => '=',
            'second'   => 'author.id',
            'boolean'  => 'and',
        ]);
});

test('QueryConverter apply conditionTree should not add joins twice', function () {
    global $bookCollection;
    $filter = new Filter(new ConditionTreeLeaf('author:name', Operators::PRESENT));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'author:name']))
        ->getQuery()->getQuery();

    expect($query->joins)->toHaveCount(1);
});

test('QueryConverter apply conditionTree should with conditionTreeBranch', function () {
    global $bookCollection;
    $filter = new Filter(
        new ConditionTreeBranch(
            'Or',
            [
                new ConditionTreeLeaf('title', Operators::PRESENT),
                new ConditionTreeLeaf('author:name', Operators::PRESENT),
            ]
        )
    );
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'title', 'author:name']))
        ->getQuery()->getQuery();

    expect($query->wheres)->toHaveCount(1)
        ->and($query->wheres[0]['type'])->toEqual('Nested')
        ->and($query->wheres[0]['query']->wheres)->toHaveCount(2);
});

// test main operators

test('QueryConverter should apply conditionTree with operator BLANK', function () {
    global $bookCollection;
    $filter = new Filter(new ConditionTreeLeaf('title', Operators::BLANK));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'title']))
        ->getQuery()->getQuery();

    expect($query->wheres[0])
        ->toEqual([
            'type'    => 'Null',
            'column'  => 'books.title',
            'boolean' => 'and',
        ]);
});

test('QueryConverter should apply conditionTree with operator PRESENT', function () {
    global $bookCollection;
    $filter = new Filter(new ConditionTreeLeaf('title', Operators::PRESENT));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'title']))
        ->getQuery()->getQuery();

    expect($query->wheres[0])
        ->toEqual([
            'type'    => 'NotNull',
            'column'  => 'books.title',
            'boolean' => 'and',
        ]);
});

test('QueryConverter should apply conditionTree with operator EQUAL', function () {
    global $bookCollection;
    $filter = new Filter(new ConditionTreeLeaf('title', Operators::EQUAL, 'foo'));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'title']))
        ->getQuery()->getQuery();

    expect($query->wheres[0])
        ->toEqual([
            'type'     => 'Basic',
            'column'   => 'books.title',
            'boolean'  => 'and',
            'operator' => '=',
            'value'    => 'foo',
        ]);
});

test('QueryConverter should apply conditionTree with operator NOT_EQUAL', function () {
    global $bookCollection;
    $filter = new Filter(new ConditionTreeLeaf('title', Operators::NOT_EQUAL, 'foo'));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'title']))
        ->getQuery()->getQuery();

    expect($query->wheres[0])
        ->toEqual([
            'type'     => 'Basic',
            'column'   => 'books.title',
            'boolean'  => 'and',
            'operator' => '!=',
            'value'    => 'foo',
        ]);
});

test('QueryConverter should apply conditionTree with operator GREATER_THAN', function () {
    global $bookCollection;
    $filter = new Filter(new ConditionTreeLeaf('id', Operators::GREATER_THAN, 1));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'title']))
        ->getQuery()->getQuery();

    expect($query->wheres[0])
        ->toEqual([
            'type'     => 'Basic',
            'column'   => 'books.id',
            'boolean'  => 'and',
            'operator' => '>',
            'value'    => 1,
        ]);
});

test('QueryConverter should apply conditionTree with operator LESS_THAN', function () {
    global $bookCollection;
    $filter = new Filter(new ConditionTreeLeaf('id', Operators::LESS_THAN, 1));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'title']))
        ->getQuery()->getQuery();

    expect($query->wheres[0])
        ->toEqual([
            'type'     => 'Basic',
            'column'   => 'books.id',
            'boolean'  => 'and',
            'operator' => '<',
            'value'    => 1,
        ]);
});

test('QueryConverter should apply conditionTree with operator ICONTAINS', function () {
    global $bookCollection;
    $filter = new Filter(new ConditionTreeLeaf('title', Operators::ICONTAINS, 'foo'));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'title']))
        ->getQuery()->getQuery();

    expect($query->wheres[0])
        ->toEqual([
            'type'     => 'Basic',
            'operator' => 'ilike',
            'value'    => '%foo%',
            'boolean'  => 'and',
            'column'   => 'books.title',
        ])
        ->and($query->bindings['where'][0])->toEqual('%foo%');
});

test('QueryConverter should apply conditionTree with operator CONTAINS', function () {
    global $bookCollection;
    $filter = new Filter(new ConditionTreeLeaf('title', Operators::CONTAINS, 'foo'));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'title']))
        ->getQuery()->getQuery();

    expect($query->wheres[0])
        ->toEqual([
            'type'     => 'Basic',
            'operator' => 'like',
            'value'    => '%foo%',
            'boolean'  => 'and',
            'column'   => 'books.title',
        ]);
});

test('QueryConverter should apply conditionTree with operator NOT_CONTAINS', function () {
    global $bookCollection;
    $filter = new Filter(new ConditionTreeLeaf('title', Operators::NOT_CONTAINS, 'foo'));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'title']))
        ->getQuery()->getQuery();

    expect($query->wheres[0])
        ->toEqual([
            'type'     => 'Basic',
            'operator' => 'not like',
            'value'    => '%foo%',
            'boolean'  => 'and',
            'column'   => 'books.title',
        ]);
});

test('QueryConverter should apply conditionTree with operator IN', function () {
    global $bookCollection;
    $filter = new Filter(new ConditionTreeLeaf('title', Operators::IN, 'foo, value'));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'title']))
        ->getQuery()->getQuery();

    expect($query->wheres[0])
        ->toEqual([
            'type'    => 'In',
            'boolean' => 'and',
            'column'  => 'books.title',
            'values'  => ['foo', 'value'],
        ]);
});

test('QueryConverter should apply conditionTree with operator NOT_IN', function () {
    global $bookCollection;
    $filter = new Filter(new ConditionTreeLeaf('title', Operators::NOT_IN, 'foo, value'));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'title']))
        ->getQuery()->getQuery();

    expect($query->wheres[0])
        ->toEqual([
            'type'    => 'NotIn',
            'boolean' => 'and',
            'column'  => 'books.title',
            'values'  => ['foo', 'value'],
        ]);
});

test('QueryConverter should apply conditionTree with operator STARTS_WITH', function () {
    global $bookCollection;
    $filter = new Filter(new ConditionTreeLeaf('title', Operators::STARTS_WITH, 'foo'));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'title']))
        ->getQuery()->getQuery();

    expect($query->wheres[0])
        ->toEqual([
            'type'     => 'Basic',
            'operator' => 'like',
            'value'    => 'foo%',
            'boolean'  => 'and',
            'column'   => 'books.title',
        ]);
});

test('QueryConverter should apply conditionTree with operator ENDS_WITH', function () {
    global $bookCollection;
    $filter = new Filter(new ConditionTreeLeaf('title', Operators::ENDS_WITH, 'foo'));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'title']))
        ->getQuery()->getQuery();

    expect($query->wheres[0])
        ->toEqual([
            'type'     => 'Basic',
            'operator' => 'like',
            'value'    => '%foo',
            'boolean'  => 'and',
            'column'   => 'books.title',
        ]);
});

test('QueryConverter should apply conditionTree with operator ISTARTS_WITH', function () {
    global $bookCollection;
    $filter = new Filter(new ConditionTreeLeaf('title', Operators::ISTARTS_WITH, 'foo'));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'title']))
        ->getQuery()->getQuery();

    expect($query->wheres[0])
        ->toEqual([
            'type'     => 'Basic',
            'operator' => 'ilike',
            'value'    => 'foo%',
            'boolean'  => 'and',
            'column'   => 'books.title',
        ]);
});

test('QueryConverter should apply conditionTree with operator IENDS_WITH', function () {
    global $bookCollection;
    $filter = new Filter(new ConditionTreeLeaf('title', Operators::IENDS_WITH, 'foo'));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'title']))
        ->getQuery()->getQuery();

    expect($query->wheres[0])
        ->toEqual([
            'type'     => 'Basic',
            'operator' => 'ilike',
            'value'    => '%foo',
            'boolean'  => 'and',
            'column'   => 'books.title',
        ]);
});

// test date operators

test('QueryConverter should apply conditionTree with operator TODAY', function () {
    global $bookCollection;
    $filter = new Filter(new ConditionTreeLeaf('published_at', Operators::TODAY));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'title', 'published_at']))
        ->getQuery()->getQuery();

    expect($query->wheres[0])
        ->toEqual([
            'type'    => 'between',
            'column'  => 'books.published_at',
            'boolean' => 'and',
            'not'     => false,
            'values'  => [
                Carbon::now(ELOQUENT_TIMEZONE)->startOfDay(),
                Carbon::now(ELOQUENT_TIMEZONE)->endOfDay(),
            ],
        ]);
});

test('QueryConverter should apply conditionTree with operator BEFORE', function () {
    global $bookCollection;
    $date = '2022-01-01 12:00:00';
    $filter = new Filter(new ConditionTreeLeaf('published_at', Operators::BEFORE, $date));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'title', 'published_at']))
        ->getQuery()->getQuery();

    expect($query->wheres[0])
        ->toEqual([
            'type'     => 'Basic',
            'column'   => 'books.published_at',
            'operator' => '<',
            'boolean'  => 'and',
            'value'    => Carbon::parse($date),
        ]);
});

test('QueryConverter should apply conditionTree with operator AFTER', function () {
    global $bookCollection;
    $date = '2022-01-01 12:00:00';
    $filter = new Filter(new ConditionTreeLeaf('published_at', Operators::AFTER, $date));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'title', 'published_at']))
        ->getQuery()->getQuery();

    expect($query->wheres[0])
        ->toEqual([
            'type'     => 'Basic',
            'column'   => 'books.published_at',
            'operator' => '>',
            'boolean'  => 'and',
            'value'    => Carbon::parse($date),
        ]);
});

test('QueryConverter should apply conditionTree with operator PREVIOUS_X_DAYS', function () {
    global $bookCollection;
    $value = 2;
    $filter = new Filter(new ConditionTreeLeaf('published_at', Operators::PREVIOUS_X_DAYS, $value));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'title', 'published_at']))
        ->getQuery()->getQuery();

    expect($query->wheres[0])
        ->toEqual([
            'type'    => 'between',
            'column'  => 'books.published_at',
            'boolean' => 'and',
            'not'     => false,
            'values'  => [
                Carbon::now(ELOQUENT_TIMEZONE)->subDays($value)->startOfDay(),
                Carbon::now(ELOQUENT_TIMEZONE)->subDay()->endOfDay(),
            ],
        ]);
});

test('QueryConverter should apply conditionTree with operator PREVIOUS_X_DAYS_TO_DATE', function () {
    global $bookCollection;
    $value = 2;
    $filter = new Filter(new ConditionTreeLeaf('published_at', Operators::PREVIOUS_X_DAYS_TO_DATE, $value));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'title', 'published_at']))
        ->getQuery()->getQuery();

    expect($query->wheres[0])
        ->toEqual([
            'type'    => 'between',
            'column'  => 'books.published_at',
            'boolean' => 'and',
            'not'     => false,
            'values'  => [
                Carbon::now(ELOQUENT_TIMEZONE)->subDays($value)->startOfDay(),
                Carbon::now(ELOQUENT_TIMEZONE)->endOfDay(),
            ],
        ]);
});

test('QueryConverter should apply conditionTree with operator PAST', function () {
    global $bookCollection;
    $filter = new Filter(new ConditionTreeLeaf('published_at', Operators::PAST));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'title', 'published_at']))
        ->getQuery()->getQuery();

    expect($query->wheres[0])
        ->toEqual([
            'type'     => 'Basic',
            'column'   => 'books.published_at',
            'operator' => '<=',
            'boolean'  => 'and',
            'value'    => Carbon::now(ELOQUENT_TIMEZONE),
        ]);
});

test('QueryConverter should apply conditionTree with operator FUTURE', function () {
    global $bookCollection;
    $filter = new Filter(new ConditionTreeLeaf('published_at', Operators::FUTURE));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'title', 'published_at']))
        ->getQuery()->getQuery();

    expect($query->wheres[0])
        ->toEqual([
            'type'     => 'Basic',
            'column'   => 'books.published_at',
            'operator' => '>=',
            'boolean'  => 'and',
            'value'    => Carbon::now(ELOQUENT_TIMEZONE),
        ]);
});

test('QueryConverter should apply conditionTree with operator BEFORE_X_HOURS_AGO', function () {
    global $bookCollection;
    $value = 2;
    $filter = new Filter(new ConditionTreeLeaf('published_at', Operators::BEFORE_X_HOURS_AGO, $value));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'title', 'published_at']))
        ->getQuery()->getQuery();

    expect($query->wheres[0])
        ->toEqual([
            'type'     => 'Basic',
            'column'   => 'books.published_at',
            'operator' => '<',
            'boolean'  => 'and',
            'value'    => Carbon::now(ELOQUENT_TIMEZONE)->subHours($value),
        ]);
});

test('QueryConverter should apply conditionTree with operator AFTER_X_HOURS_AGO', function () {
    global $bookCollection;
    $value = 2;
    $filter = new Filter(new ConditionTreeLeaf('published_at', Operators::AFTER_X_HOURS_AGO, $value));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'title', 'published_at']))
        ->getQuery()->getQuery();

    expect($query->wheres[0])
        ->toEqual([
            'type'     => 'Basic',
            'column'   => 'books.published_at',
            'operator' => '>',
            'boolean'  => 'and',
            'value'    => Carbon::now(ELOQUENT_TIMEZONE)->subHours($value),
        ]);
});

test('QueryConverter should apply conditionTree with operator YESTERDAY', function () {
    global $bookCollection;
    $filter = new Filter(new ConditionTreeLeaf('published_at', Operators::YESTERDAY));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'title', 'published_at']))
        ->getQuery()->getQuery();

    expect($query->wheres[0])
        ->toEqual([
            'type'    => 'between',
            'column'  => 'books.published_at',
            'boolean' => 'and',
            'not'     => false,
            'values'  => [
                Carbon::now(ELOQUENT_TIMEZONE)->subDay()->startOfDay(),
                Carbon::now(ELOQUENT_TIMEZONE)->subDay()->endOfDay(),
            ],
        ]);
});

test('QueryConverter should apply conditionTree with operator PREVIOUS_WEEK', function () {
    global $bookCollection;
    $filter = new Filter(new ConditionTreeLeaf('published_at', Operators::PREVIOUS_WEEK));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'title', 'published_at']))
        ->getQuery()->getQuery();

    expect($query->wheres[0])
        ->toEqual([
            'type'    => 'between',
            'column'  => 'books.published_at',
            'boolean' => 'and',
            'not'     => false,
            'values'  => [
                Carbon::now(ELOQUENT_TIMEZONE)->subWeek()->startOfWeek(),
                Carbon::now(ELOQUENT_TIMEZONE)->subWeek()->endOfWeek(),
            ],
        ]);
});

test('QueryConverter should apply conditionTree with operator PREVIOUS_MONTH', function () {
    global $bookCollection;
    $filter = new Filter(new ConditionTreeLeaf('published_at', Operators::PREVIOUS_MONTH));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'title', 'published_at']))
        ->getQuery()->getQuery();

    expect($query->wheres[0])
        ->toEqual([
            'type'    => 'between',
            'column'  => 'books.published_at',
            'boolean' => 'and',
            'not'     => false,
            'values'  => [
                Carbon::now(ELOQUENT_TIMEZONE)->subMonth()->startOfMonth(),
                Carbon::now(ELOQUENT_TIMEZONE)->subMonth()->endOfMonth(),
            ],
        ]);
});

test('QueryConverter should apply conditionTree with operator PREVIOUS_QUARTER', function () {
    global $bookCollection;
    $filter = new Filter(new ConditionTreeLeaf('published_at', Operators::PREVIOUS_QUARTER));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'title', 'published_at']))
        ->getQuery()->getQuery();

    expect($query->wheres[0])
        ->toEqual([
            'type'    => 'between',
            'column'  => 'books.published_at',
            'boolean' => 'and',
            'not'     => false,
            'values'  => [
                Carbon::now(ELOQUENT_TIMEZONE)->subQuarter()->startOfQuarter(),
                Carbon::now(ELOQUENT_TIMEZONE)->subQuarter()->endOfQuarter(),
            ],
        ]);
});

test('QueryConverter should apply conditionTree with operator PREVIOUS_YEAR', function () {
    global $bookCollection;
    $filter = new Filter(new ConditionTreeLeaf('published_at', Operators::PREVIOUS_YEAR));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'title', 'published_at']))
        ->getQuery()->getQuery();

    expect($query->wheres[0])
        ->toEqual([
            'type'    => 'between',
            'column'  => 'books.published_at',
            'boolean' => 'and',
            'not'     => false,
            'values'  => [
                Carbon::now(ELOQUENT_TIMEZONE)->subYear()->startOfYear(),
                Carbon::now(ELOQUENT_TIMEZONE)->subYear()->endOfYear(),
            ],
        ]);
});

test('QueryConverter should apply conditionTree with operator PREVIOUS_WEEK_TO_DATE', function () {
    global $bookCollection;
    $filter = new Filter(new ConditionTreeLeaf('published_at', Operators::PREVIOUS_WEEK_TO_DATE));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'title', 'published_at']))
        ->getQuery()->getQuery();

    expect($query->wheres[0])
        ->toEqual([
            'type'    => 'between',
            'column'  => 'books.published_at',
            'boolean' => 'and',
            'not'     => false,
            'values'  => [
                Carbon::now(ELOQUENT_TIMEZONE)->startOfWeek(),
                Carbon::now(ELOQUENT_TIMEZONE),
            ],
        ]);
});

test('QueryConverter should apply conditionTree with operator PREVIOUS_MONTH_TO_DATE', function () {
    global $bookCollection;
    $filter = new Filter(new ConditionTreeLeaf('published_at', Operators::PREVIOUS_MONTH_TO_DATE));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'title', 'published_at']))
        ->getQuery()->getQuery();

    expect($query->wheres[0])
        ->toEqual([
            'type'    => 'between',
            'column'  => 'books.published_at',
            'boolean' => 'and',
            'not'     => false,
            'values'  => [
                Carbon::now(ELOQUENT_TIMEZONE)->startOfMonth(),
                Carbon::now(ELOQUENT_TIMEZONE),
            ],
        ]);
});

test('QueryConverter should apply conditionTree with operator PREVIOUS_QUARTER_TO_DATE', function () {
    global $bookCollection;
    $filter = new Filter(new ConditionTreeLeaf('published_at', Operators::PREVIOUS_QUARTER_TO_DATE));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'title', 'published_at']))
        ->getQuery()->getQuery();

    expect($query->wheres[0])
        ->toEqual([
            'type'    => 'between',
            'column'  => 'books.published_at',
            'boolean' => 'and',
            'not'     => false,
            'values'  => [
                Carbon::now(ELOQUENT_TIMEZONE)->startOfQuarter(),
                Carbon::now(ELOQUENT_TIMEZONE),
            ],
        ]);
});

test('QueryConverter should apply conditionTree with operator PREVIOUS_YEAR_TO_DATE', function () {
    global $bookCollection;
    $filter = new Filter(new ConditionTreeLeaf('published_at', Operators::PREVIOUS_YEAR_TO_DATE));
    $query = QueryConverter::of($bookCollection, 'Europe/Paris', $filter, new Projection(['id', 'title', 'published_at']))
        ->getQuery()->getQuery();

    expect($query->wheres[0])
        ->toEqual([
            'type'    => 'between',
            'column'  => 'books.published_at',
            'boolean' => 'and',
            'not'     => false,
            'values'  => [
                Carbon::now(ELOQUENT_TIMEZONE)->startOfYear(),
                Carbon::now(ELOQUENT_TIMEZONE),
            ],
        ]);
});
