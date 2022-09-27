<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\FilterFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\OneToManySchema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

const TEST_TIMEZONE = 'Europe/Dublin';

dataset('DatasourceForFilterFactory', dataset: function () {
    yield $datasource = new Datasource();

    $collectionBooks = new Collection($datasource, 'books');
    $collectionBooks->addFields(
        [
            'id'          => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'reviews'     => new ManyToManySchema(
                foreignKey: 'review_id',
                foreignKeyTarget: 'id',
                throughTable: 'bookReview',
                originKey: 'book_id',
                originKeyTarget: 'id',
                foreignCollection: 'reviews'
            ),
            'bookReviews' => new OneToManySchema(
                originKey: 'book_id',
                originKeyTarget: 'id',
                foreignCollection: 'reviews',
            ),
        ]
    );

    $collectionReviews = new Collection($datasource, 'reviews');
    $collectionReviews->addFields(
        [
            'id' => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
        ]
    );
    $mockCollectionReviews = mock($collectionReviews)
        ->shouldReceive('list')
        ->andReturn([['id' => 123]])
        ->getMock();

    $collectionBookReview = new Collection($datasource, 'bookReview');
    $collectionBookReview->addFields(
        [
            'id'        => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'review_id' => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'review'    => new ManyToOneSchema(
                foreignKey: 'review_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'reviews'
            ),
        ]
    );
    $mockCollectionBookReview = mock($collectionBookReview)
        ->shouldReceive('list')
        ->andReturn([['id' => 123, 'review_id' => 1], ['id' => 124, 'review_id' => 2]])
        ->getMock();

    $datasource->addCollection($collectionBooks);
    $datasource->addCollection($mockCollectionReviews);
    $datasource->addCollection($mockCollectionBookReview);

    $options = [
        'projectDir' => sys_get_temp_dir(), // only use for cache
    ];
    (new AgentFactory($options,  []))->addDatasources([$datasource]);
});

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

//test("makeThroughFilter() should nest the provided filter many to many", closure: function (Datasource $datasource, Caller $caller) {
//    $books = $datasource->getCollection('books');
//    $baseFilter = new Filter(conditionTree: new ConditionTreeLeaf('someField', Operators::EQUAL, 1));
//    $filter = FilterFactory::makeThroughFilter($books, [1], 'reviews', $caller, $baseFilter);
//
//    expect($filter)
//        ->toEqual(
//            new Filter(
//                conditionTree: new ConditionTreeBranch(
//                    aggregator: 'And',
//                    conditions: [
//                        new ConditionTreeLeaf(field: 'book_id', operator: Operators::EQUAL, value: 1),
//                        new ConditionTreeLeaf(field: 'review_id', operator: Operators::PRESENT),
//                        new ConditionTreeLeaf(field: 'reviews:someField', operator: Operators::EQUAL, value: 1),
//                    ]
//                )
//            )
//        );
//})->with('DatasourceForFilterFactory')->with('caller');
//
//test("makeThroughFilter() should make two queries many to many", closure: function (Datasource $datasource, Caller $caller) {
//    $books = $datasource->getCollection('books');
//    $baseFilter = new Filter(conditionTree: new ConditionTreeLeaf('someField', Operators::EQUAL, 1), segment: 'someSegment');
//    $filter = FilterFactory::makeThroughFilter($books, [1], 'reviews', $caller, $baseFilter);
//    expect($filter)
//        ->toEqual(
//            new Filter(
//                conditionTree: new ConditionTreeBranch(
//                    aggregator: 'And',
//                    conditions: [
//                        new ConditionTreeLeaf(field: 'book_id', operator: Operators::EQUAL, value: 1),
//                        new ConditionTreeLeaf(field: 'review_id', operator: Operators::IN, value: [123]),
//                    ]
//                )
//            )
//        );
//})->with('DatasourceForFilterFactory')->with('caller');
//
//test("makeForeignFilter() should add the fk condition one to many", closure: function (Datasource $datasource, Caller $caller) {
//    $books = $datasource->getCollection('books');
//    $baseFilter = new Filter(
//        conditionTree: new ConditionTreeLeaf('someField', Operators::EQUAL, 1),
//        segment: 'some-segment'
//    );
//    $filter = FilterFactory::makeForeignFilter($books, [1], 'bookReviews', $caller, $baseFilter);
//
//    expect($filter)
//        ->toEqual(
//            new Filter(
//                conditionTree: new ConditionTreeBranch(
//                    aggregator: 'And',
//                    conditions: [
//                        new ConditionTreeLeaf(field: 'someField', operator: Operators::EQUAL, value: 1),
//                        new ConditionTreeLeaf(field: 'book_id', operator: Operators::EQUAL, value: 1),
//                    ]
//                ),
//                segment: 'some-segment'
//            )
//        );
//})->with('DatasourceForFilterFactory')->with('caller');
//
//test("makeForeignFilter() should query the through collection many to many", closure: function (Datasource $datasource, Caller $caller) {
//    $books = $datasource->getCollection('books');
//    $baseFilter = new Filter(conditionTree: new ConditionTreeLeaf('someField', Operators::EQUAL, 1), segment: 'some-segment');
//    $filter = FilterFactory::makeForeignFilter($books, [1], 'reviews', $caller, $baseFilter);
//
//    expect($filter)
//        ->toEqual(
//            new Filter(
//                conditionTree: new ConditionTreeBranch(
//                    aggregator: 'And',
//                    conditions: [
//                        new ConditionTreeLeaf(field: 'someField', operator: Operators::EQUAL, value: 1),
//                        new ConditionTreeLeaf(field: 'id', operator: Operators::IN, value: [1, 2]),
//                    ]
//                ),
//                segment: 'some-segment'
//            )
//        );
//})->with('DatasourceForFilterFactory')->with('caller');
