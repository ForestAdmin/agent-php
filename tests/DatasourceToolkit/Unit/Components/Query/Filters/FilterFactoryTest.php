<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\FilterFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\OneToManySchema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

const TEST_TIMEZONE = 'Europe/Dublin';
const TEST_DATE = '2022-02-16T10:00:00.000Z';

dataset('DatasourceForFilterFactory', function () {
    yield $datasource = new Datasource();
    $collectionBooks = new Collection($datasource, 'books');
    $collectionBooks->addFields(
        [
            'id'          => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'reviews'     => new ManyToManySchema(
                foreignKey: 'review_id',
                foreignKeyTarget: 'id',
                throughCollection: 'bookReview',
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
    $datasource->addCollection($collectionBooks);
    $datasource->addCollection($collectionReviews);
    $datasource->addCollection($collectionBookReview);

    $options = [
        'projectDir' => sys_get_temp_dir(), // only use for cache
    ];
    new AgentFactory($options);
    cache('datasource', $datasource);
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
    ['Today', 'Yesterday'],
    ['PreviousWeekToDate', 'PreviousWeek'],
    ['PreviousMonthToDate', 'PreviousMonth'],
    ['PreviousQuarterToDate', 'PreviousQuarter'],
    ['PreviousYearToDate', 'PreviousYear'],
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
                    new ConditionTreeLeaf('someField', 'GreaterThan', $startPeriod->toDateTimeString()),
                    new ConditionTreeLeaf('someField', 'LessThan', $endPeriod->toDateTimeString()),
                ]
            )
        );
})->with([
    ['Yesterday', 'Day'],
    ['PreviousWeek', 'Week'],
    ['PreviousMonth', 'Month'],
    ['PreviousQuarter', 'Quarter'],
    ['PreviousYear', 'Year'],
]);

test("getPreviousPeriodFilter() should replace PreviousXDays operator by a greater/less than", closure: function () {
    $filter = new Filter(
        conditionTree: new ConditionTreeLeaf('someField', 'PreviousXDays', 3)
    );
    $startPeriod = Carbon::now(TEST_TIMEZONE)->subDays(2 * $filter->getConditionTree()->getValue())->startOfDay();
    $endPeriod = Carbon::now(TEST_TIMEZONE)->subDays($filter->getConditionTree()->getValue())->startOfDay();

    expect(FilterFactory::getPreviousPeriodFilter($filter, TEST_TIMEZONE)->getConditionTree())
        ->toEqual(
            new ConditionTreeBranch(
                aggregator: 'And',
                conditions: [
                    new ConditionTreeLeaf('someField', 'GreaterThan', $startPeriod->toDateTimeString()),
                    new ConditionTreeLeaf('someField', 'LessThan', $endPeriod->toDateTimeString()),
                ]
            )
        );
});

test("getPreviousPeriodFilter() should replace PreviousXDaysToDate operator by a greater/less than", closure: function () {
    $filter = new Filter(
        conditionTree: new ConditionTreeLeaf('someField', 'PreviousXDaysToDate', 3)
    );
    $startPeriod = Carbon::now(TEST_TIMEZONE)->subDays(2 * $filter->getConditionTree()->getValue())->startOfDay();
    $endPeriod = Carbon::now(TEST_TIMEZONE)->subDays($filter->getConditionTree()->getValue());

    expect(FilterFactory::getPreviousPeriodFilter($filter, TEST_TIMEZONE)->getConditionTree())
        ->toEqual(
            new ConditionTreeBranch(
                aggregator: 'And',
                conditions: [
                    new ConditionTreeLeaf('someField', 'GreaterThan', $startPeriod->toDateTimeString()),
                    new ConditionTreeLeaf('someField', 'LessThan', $endPeriod->toDateTimeString()),
                ]
            )
        );
});

