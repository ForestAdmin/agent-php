<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\FilterFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\Tests\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

const TEST_TIMEZONE = 'Europe/Paris';

test('getPreviousPeriodFilter() when no interval operator is present in the condition tree should not modify the condition tree', closure: function () {
    $leaf = new ConditionTreeLeaf('someField', 'Like', 'someValue');
    $filter = new Filter(conditionTree: $leaf);

    expect(FilterFactory::getPreviousPeriodFilter($filter, TEST_TIMEZONE))
        ->toEqual($filter);
});

test("getPreviousPeriodFilter() should override baseOperator by previousOperator", closure: function ($baseOperator, $previousOperator) {
    $filter = new Filter(
        conditionTree: new ConditionTreeLeaf('someField', $baseOperator, 'someValue')
    );

    expect(FilterFactory::getPreviousPeriodFilter($filter, TEST_TIMEZONE)->getConditionTree())
        ->toEqual(new ConditionTreeLeaf('someField', $previousOperator, 'someValue'));
})->with([
    [Operators::TODAY, Operators::YESTERDAY],
    [Operators::PREVIOUS_WEEK_TO_DATE, Operators::PREVIOUS_WEEK],
    [Operators::PREVIOUS_MONTH_TO_DATE, Operators::PREVIOUS_MONTH],
    [Operators::PREVIOUS_QUARTER_TO_DATE, Operators::PREVIOUS_QUARTER],
    [Operators::PREVIOUS_YEAR_TO_DATE, Operators::PREVIOUS_YEAR],
]);

test("getPreviousPeriodFilter() should replace baseOperator by a greater/less than operator", closure: function ($baseOperator, $unit) {
    $filter = new Filter(
        conditionTree: new ConditionTreeLeaf('someField', $baseOperator, 'someValue')
    );
    $sub = 'sub' . Str::plural($unit);
    $start = 'startOf' . $unit;
    $end = 'endOf' . $unit;
    $startPeriod = Carbon::now(TEST_TIMEZONE)->$sub(2)->$start();
    $endPeriod = Carbon::now(TEST_TIMEZONE)->$sub(2)->$end();

    expect(FilterFactory::getPreviousPeriodFilter($filter, TEST_TIMEZONE)->getConditionTree())
        ->toEqual(
            new ConditionTreeBranch(
                aggregator: 'And',
                conditions: [
                    new ConditionTreeLeaf('someField', Operators::GREATER_THAN, $startPeriod->toDateTimeString()),
                    new ConditionTreeLeaf('someField', Operators::LESS_THAN, $endPeriod->toDateTimeString()),
                ]
            )
        );
})->with([
    [Operators::YESTERDAY, 'Day'],
    [Operators::PREVIOUS_WEEK, 'Week'],
    [Operators::PREVIOUS_MONTH, 'Month'],
    [Operators::PREVIOUS_QUARTER, 'Quarter'],
    [Operators::PREVIOUS_YEAR, 'Year'],
]);

test("getPreviousPeriodFilter() should replace PreviousXDays operator by a greater/less than", closure: function () {
    $filter = new Filter(
        conditionTree: new ConditionTreeLeaf('someField', Operators::PREVIOUS_X_DAYS, 3)
    );
    $startPeriod = Carbon::now(TEST_TIMEZONE)->subDays(2 * $filter->getConditionTree()->getValue())->startOfDay();
    $endPeriod = Carbon::now(TEST_TIMEZONE)->subDays($filter->getConditionTree()->getValue())->startOfDay();

    expect(FilterFactory::getPreviousPeriodFilter($filter, TEST_TIMEZONE)->getConditionTree())
        ->toEqual(
            new ConditionTreeBranch(
                aggregator: 'And',
                conditions: [
                    new ConditionTreeLeaf('someField', Operators::GREATER_THAN, $startPeriod->toDateTimeString()),
                    new ConditionTreeLeaf('someField', Operators::LESS_THAN, $endPeriod->toDateTimeString()),
                ]
            )
        );
});

test("getPreviousPeriodFilter() should replace PreviousXDaysToDate operator by a greater/less than", closure: function () {
    $filter = new Filter(
        conditionTree: new ConditionTreeLeaf('someField', Operators::PREVIOUS_X_DAYS_TO_DATE, 3)
    );
    $startPeriod = Carbon::now(TEST_TIMEZONE)->subDays(2 * $filter->getConditionTree()->getValue())->startOfDay();
    $endPeriod = Carbon::now(TEST_TIMEZONE)->subDays($filter->getConditionTree()->getValue());

    expect(FilterFactory::getPreviousPeriodFilter($filter, TEST_TIMEZONE)->getConditionTree())
        ->toEqual(
            new ConditionTreeBranch(
                aggregator: 'And',
                conditions: [
                    new ConditionTreeLeaf('someField', Operators::GREATER_THAN, $startPeriod->toDateTimeString()),
                    new ConditionTreeLeaf('someField', Operators::LESS_THAN, $endPeriod->toDateTimeString()),
                ]
            )
        );
});


describe('makeThroughFilter()', function () {
    $before = static function (TestCase $testCase) {
        $datasource = new Datasource();

        $collectionBooks = new Collection($datasource, 'Book');
        $collectionBooks->addFields(
            [
                'id'          => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
                'reviews'     => new ManyToManySchema(
                    originKey: 'book_id',
                    originKeyTarget: 'id',
                    foreignKey: 'review_id',
                    foreignKeyTarget: 'id',
                    foreignCollection: 'Review',
                    throughCollection: 'BookReview'
                ),
                'bookReviews' => new OneToManySchema(
                    originKey: 'book_id',
                    originKeyTarget: 'id',
                    foreignCollection: 'Review',
                ),
            ]
        );

        $collectionReviews = new Collection($datasource, 'Review');
        $collectionReviews->addFields(
            [
                'id' => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            ]
        );
        $mockCollectionReviews = \Mockery::mock($collectionReviews)
            ->shouldReceive('list')
            ->andReturn([['id' => 1], ['id' => 2]])
            ->getMock();

        $collectionBookReview = new Collection($datasource, 'BookReview');
        $collectionBookReview->addFields(
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
        $mockCollectionBookReview = \Mockery::mock($collectionBookReview)
            ->shouldReceive('list')
            ->andReturn([['id' => 123, 'review_id' => 1], ['id' => 124, 'review_id' => 2]])
            ->getMock();

        $datasource->addCollection($collectionBooks);
        $datasource->addCollection($mockCollectionReviews);
        $datasource->addCollection($mockCollectionBookReview);

        $testCase->buildAgent($datasource);

        $testCase->bucket['datasource'] = $datasource;
    };

    test("should nest the provided filter many to many", function (Caller $caller) use ($before) {
        $before($this);
        $datasource = $this->bucket['datasource'];
        $books = $datasource->getCollection('Book');
        $baseFilter = new Filter(conditionTree: new ConditionTreeLeaf('someField', Operators::EQUAL, 1));
        $filter = FilterFactory::makeThroughFilter($books, [1], 'reviews', $caller, $baseFilter);

        expect($filter)
            ->toEqual(
                new Filter(
                    conditionTree: new ConditionTreeBranch(
                        aggregator: 'And',
                        conditions: [
                            new ConditionTreeLeaf(field: 'book_id', operator: Operators::EQUAL, value: 1),
                            new ConditionTreeLeaf(field: 'review_id', operator: Operators::PRESENT),
                            new ConditionTreeLeaf(field: 'review:someField', operator: Operators::EQUAL, value: 1),
                        ]
                    )
                )
            );
    })->with('caller');

    test("should make two queries many to many", function (Caller $caller) use ($before) {
        $before($this);
        $datasource = $this->bucket['datasource'];
        $books = $datasource->getCollection('Book');
        $baseFilter = new Filter(conditionTree: new ConditionTreeLeaf('someField', Operators::EQUAL, 1), segment: 'someSegment');
        $filter = FilterFactory::makeThroughFilter($books, [1], 'reviews', $caller, $baseFilter);

        expect($filter)
            ->toEqual(
                new Filter(
                    conditionTree: new ConditionTreeBranch(
                        aggregator: 'And',
                        conditions: [
                            new ConditionTreeLeaf(field: 'book_id', operator: Operators::EQUAL, value: 1),
                            new ConditionTreeLeaf(field: 'review_id', operator: Operators::IN, value: [1, 2]),
                        ]
                    )
                )
            );
    })->with('caller');

    test("should add the fk condition one to many", function (Caller $caller) use ($before) {
        $before($this);
        $datasource = $this->bucket['datasource'];
        $books = $datasource->getCollection('Book');
        $baseFilter = new Filter(
            conditionTree: new ConditionTreeLeaf('someField', Operators::EQUAL, 1),
            segment: 'some-segment'
        );
        $filter = FilterFactory::makeForeignFilter($books, [1], 'bookReviews', $caller, $baseFilter);

        expect($filter)
            ->toEqual(
                new Filter(
                    conditionTree: new ConditionTreeBranch(
                        aggregator: 'And',
                        conditions: [
                            new ConditionTreeLeaf(field: 'someField', operator: Operators::EQUAL, value: 1),
                            new ConditionTreeLeaf(field: 'book_id', operator: Operators::EQUAL, value: 1),
                        ]
                    ),
                    segment: 'some-segment'
                )
            );
    })->with('caller');

    test("should query the through collection many to many", function (Caller $caller) use ($before) {
        $before($this);
        $datasource = $this->bucket['datasource'];
        $books = $datasource->getCollection('Book');
        $baseFilter = new Filter(conditionTree: new ConditionTreeLeaf('someField', Operators::EQUAL, 1), segment: 'some-segment');
        $filter = FilterFactory::makeForeignFilter($books, [1], 'reviews', $caller, $baseFilter);
        expect($filter)
            ->toEqual(
                new Filter(
                    conditionTree: new ConditionTreeBranch(
                        aggregator: 'And',
                        conditions: [
                            new ConditionTreeLeaf(field: 'someField', operator: Operators::EQUAL, value: 1),
                            new ConditionTreeLeaf(field: 'id', operator: Operators::IN, value: [1, 2]),
                        ]
                    ),
                    segment: 'some-segment'
                )
            );
    })->with('caller');
});
