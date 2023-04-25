<?php

use ForestAdmin\AgentPHP\DatasourceCustomizer\Context\CollectionCustomizationContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Search\SearchCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;

function factorySearchCollection()
{
    $datasource = new Datasource();
    $collectionPerson = new Collection($datasource, 'Person');
    $datasource->addCollection($collectionPerson);
    buildAgent($datasource);

    return [$datasource, $collectionPerson];
}

test('replaceSearch() should work', function () {
    [$datasource, $collection] = factorySearchCollection();
    $searchCollection = new SearchCollection($collection, $datasource);
    $replace = fn ($search) => [
        'aggregator' => 'And',
        'conditions' => [
            ['field' => 'id', 'operator' => Operators::EQUAL, 'value' => 1],
        ],
    ];
    $searchCollection->replaceSearch($replace);

    expect(invokeProperty($searchCollection, 'replacer'))->toEqual($replace);
});

test('isSearchable() should return true', function () {
    [$datasource, $collection] = factorySearchCollection();
    $searchCollection = new SearchCollection($collection, $datasource);

    expect($searchCollection->isSearchable())->toBeTrue();
});

test('refineFilter() when the search value is null should return the given filter to return all records', function (Caller $caller) {
    [$datasource, $collection] = factorySearchCollection();
    $searchCollection = new SearchCollection($collection, $datasource);
    $filter = new Filter(search: null);

    expect($searchCollection->refineFilter($caller, $filter))->toEqual($filter);
})->with('caller');

test('refineFilter() when the collection schema is searchable should return the given filter without adding condition', function (Caller $caller) {
    [$datasource, $collection] = factorySearchCollection();
    $collection->setSearchable(true);
    $searchCollection = new SearchCollection($collection, $datasource);
    $filter = new Filter(search: 'a text');

    expect($searchCollection->refineFilter($caller, $filter))->toEqual($filter);
})->with('caller');

test('refineFilter() when a replacer is provided it should be used instead of the default one', function (Caller $caller) {
    [$datasource, $collection] = factorySearchCollection();
    $searchCollection = new SearchCollection($collection, $datasource);
    $filter = new Filter(search: 'something');
    $replace = fn ($value) => [
        'field' => 'id', 'operator' => Operators::EQUAL, 'value' => $value,
    ];
    $searchCollection->replaceSearch($replace);

    expect($searchCollection->refineFilter($caller, $filter))->toEqual(
        new Filter(
            conditionTree: ConditionTreeFactory::fromArray(['field' => 'id', 'operator' => Operators::EQUAL, 'value' => 'something']),
            search: null,
            segment: null
        ),
    );
})->with('caller');

test('refineFilter() when the search is defined and the collection schema is not searchable and when the search is empty returns the same filter and set search as null', function (Caller $caller) {
    [$datasource, $collection] = factorySearchCollection();
    $collection->setSearchable(false);
    $searchCollection = new SearchCollection($collection, $datasource);
    $filter = new Filter(search: '     ', searchExtended: false);

    expect($searchCollection->refineFilter($caller, $filter))->toEqual(
        new Filter(
            conditionTree: null,
            search: null,
            segment: null
        ),
    );
})->with('caller');

test('refineFilter() when the search is defined and the collection schema is not searchable and when the filter contains already conditions should add its conditions to the filter', function (Caller $caller) {
    [$datasource, $collection] = factorySearchCollection();
    $collection->setSearchable(false);
    $collection->setField('label', new ColumnSchema(columnType: PrimitiveType::STRING, filterOperators: [Operators::ICONTAINS]));
    $searchCollection = new SearchCollection($collection, $datasource);
    $filter = new Filter(
        conditionTree: new ConditionTreeBranch(
            'And',
            [new ConditionTreeLeaf(field: 'label', operator: Operators::EQUAL, value: 'value')]
        ),
        search: 'a text',
    );

    expect($searchCollection->refineFilter($caller, $filter))->toEqual(
        new Filter(
            conditionTree: new ConditionTreeBranch(
                'And',
                [
                    new ConditionTreeLeaf(field: 'label', operator: Operators::EQUAL, value: 'value'),
                    new ConditionTreeLeaf(field: 'label', operator: Operators::ICONTAINS, value: 'a text'),
                ]
            ),
            search: null,
            searchExtended: false,
            segment: null
        ),
    );
})->with('caller');

test('refineFilter() when the search is defined and the collection schema is not searchable when the search is a string and the column type is a string should return filter with "contains" condition and "or" aggregator', function (Caller $caller) {
    [$datasource, $collection] = factorySearchCollection();
    $collection->setSearchable(false);
    $collection->setField('label', new ColumnSchema(columnType: PrimitiveType::STRING, filterOperators: [Operators::ICONTAINS, Operators::CONTAINS]));
    $searchCollection = new SearchCollection($collection, $datasource);
    $filter = new Filter(search: 'a text');

    expect($searchCollection->refineFilter($caller, $filter))->toEqual(
        new Filter(
            conditionTree: ConditionTreeFactory::fromArray(['field' => 'label', 'operator' => Operators::CONTAINS, 'value' => 'a text']),
            search: null,
            searchExtended: null,
            segment: null
        ),
    );
})->with('caller');

test('refineFilter() when the search is defined and the collection schema is not searchable when searching on a string that only supports Equal should return filter with "equal" condition', function (Caller $caller) {
    [$datasource, $collection] = factorySearchCollection();
    $collection->setSearchable(false);
    $collection->setField('label', new ColumnSchema(columnType: PrimitiveType::STRING, filterOperators: [Operators::EQUAL]));
    $searchCollection = new SearchCollection($collection, $datasource);
    $filter = new Filter(search: 'a text');

    expect($searchCollection->refineFilter($caller, $filter))->toEqual(
        new Filter(
            conditionTree: ConditionTreeFactory::fromArray(['field' => 'label', 'operator' => Operators::EQUAL, 'value' => 'a text']),
            search: null,
            searchExtended: null,
            segment: null
        ),
    );
})->with('caller');

test('refineFilter() when the search is defined and the collection schema is not searchable search is a case insensitive string and both operators are supported should return filter with "contains" condition and "or" aggregator', function (Caller $caller) {
    [$datasource, $collection] = factorySearchCollection();
    $collection->setSearchable(false);
    $collection->setField('label', new ColumnSchema(columnType: PrimitiveType::STRING, filterOperators: [Operators::ICONTAINS, Operators::CONTAINS]));
    $searchCollection = new SearchCollection($collection, $datasource);
    $filter = new Filter(search: '@#*$(@#*$(23423423');

    expect($searchCollection->refineFilter($caller, $filter))->toEqual(
        new Filter(
            conditionTree: ConditionTreeFactory::fromArray(['field' => 'label', 'operator' => Operators::CONTAINS, 'value' => '@#*$(@#*$(23423423']),
            search: null,
            searchExtended: null,
            segment: null
        ),
    );
})->with('caller');

test('refineFilter() when the search is defined and the collection schema is not searchable when the search is an uuid and the column type is an uuid should return filter with "equal" condition and "or" aggregator', function (Caller $caller) {
    [$datasource, $collection] = factorySearchCollection();
    $collection->setSearchable(false);
    $collection->setField('number', new ColumnSchema(columnType: PrimitiveType::UUID, filterOperators: [Operators::EQUAL]));
    $searchCollection = new SearchCollection($collection, $datasource);
    $filter = new Filter(search: '2d162303-78bf-599e-b197-93590ac3d315');

    expect($searchCollection->refineFilter($caller, $filter))->toEqual(
        new Filter(
            conditionTree: ConditionTreeFactory::fromArray(['field' => 'number', 'operator' => Operators::EQUAL, 'value' => '2d162303-78bf-599e-b197-93590ac3d315']),
            search: null,
            searchExtended: null,
            segment: null
        ),
    );
})->with('caller');

test('refineFilter() when the search is defined and the collection schema is not searchable when the search is a number and the column type is a number returns "equal" condition, "or" aggregator and cast value to Number', function (Caller $caller) {
    [$datasource, $collection] = factorySearchCollection();
    $collection->setSearchable(false);
    $collection->setField('label', new ColumnSchema(columnType: PrimitiveType::STRING, filterOperators: [Operators::ICONTAINS]));
    $collection->setField('number', new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::EQUAL]));
    $searchCollection = new SearchCollection($collection, $datasource);
    $filter = new Filter(search: '1584');

    expect($searchCollection->refineFilter($caller, $filter))->toEqual(
        new Filter(
            conditionTree: new ConditionTreeBranch(
                'Or',
                [
                    new ConditionTreeLeaf(field: 'label', operator: Operators::ICONTAINS, value: '1584'),
                    new ConditionTreeLeaf(field: 'number', operator: Operators::EQUAL, value: 1584),
                ]
            ),
            search: null,
            searchExtended: null,
            segment: null
        ),
    );
})->with('caller');

test('refineFilter() when the search is defined and the collection schema is not searchable when the search is an string and the column type is an enum should return filter with "equal" condition and "or" aggregator', function (Caller $caller) {
    [$datasource, $collection] = factorySearchCollection();
    $collection->setSearchable(false);
    $collection->setField('label', new ColumnSchema(columnType: PrimitiveType::ENUM, filterOperators: [Operators::EQUAL], enumValues: ['AnEnUmVaLue']));
    $searchCollection = new SearchCollection($collection, $datasource);
    $filter = new Filter(search: 'anenumvalue');

    expect($searchCollection->refineFilter($caller, $filter))->toEqual(
        new Filter(
            conditionTree: new ConditionTreeLeaf(field: 'label', operator: Operators::EQUAL, value: 'AnEnUmVaLue'),
            search: null,
            searchExtended: null,
            segment: null
        ),
    );
})->with('caller');

test('refineFilter() when the search is defined and the collection schema is not searchable when there are several fields should return all the number fields when a number is researched', function (Caller $caller) {
    [$datasource, $collection] = factorySearchCollection();
    $collection->setSearchable(false);
    $collection->setField('field1', new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::EQUAL]));
    $collection->setField('field2', new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::EQUAL]));
    $collection->setField('fieldNotReturned', new ColumnSchema(columnType: PrimitiveType::UUID));
    $searchCollection = new SearchCollection($collection, $datasource);
    $filter = new Filter(search: '1584');

    expect($searchCollection->refineFilter($caller, $filter))->toEqual(
        new Filter(
            conditionTree: new ConditionTreeBranch(
                'Or',
                [
                    new ConditionTreeLeaf(field: 'field1', operator: Operators::EQUAL, value: 1584),
                    new ConditionTreeLeaf(field: 'field2', operator: Operators::EQUAL, value: 1584),
                ]
            ),
            search: null,
            searchExtended: null,
            segment: null
        ),
    );
})->with('caller');

test('refineFilter() when the search is defined and the collection schema is not searchable when it is a deep search with relation fields should return all the uuid fields when uuid is researched', function (Caller $caller) {
    $datasource = new Datasource();
    $collectionBooks = new Collection($datasource, 'Book');
    $collectionBooks->addFields(
        [
            'id'          => new ColumnSchema(columnType: PrimitiveType::UUID, filterOperators: [Operators::EQUAL], isPrimaryKey: true),
            'reviews'     => new ManyToManySchema(
                originKey: 'book_id',
                originKeyTarget: 'id',
                foreignKey: 'review_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'Review',
                throughCollection: 'BookReview',
            ),
            'bookReviews' => new OneToManySchema(
                originKey: 'book_id',
                originKeyTarget: 'id',
                foreignCollection: 'Review',
            ),
        ]
    );
    $collectionBookReview = new Collection($datasource, 'BookReview');
    $collectionBookReview->addFields(
        [
            'id'      => new ColumnSchema(columnType: PrimitiveType::UUID, filterOperators: [Operators::EQUAL], isPrimaryKey: true),
            'reviews' => new ManyToManySchema(
                originKey: 'book_id',
                originKeyTarget: 'id',
                foreignKey: 'review_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'Review',
                throughCollection: 'BookReview',
            ),
            'book'    => new ManyToOneSchema(
                foreignKey: 'book_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'Book',
            ),
            'review'  => new OneToOneSchema(
                originKey: 'review_id',
                originKeyTarget: 'id',
                foreignCollection: 'Review',
            ),
        ]
    );

    $collectionReviews = new Collection($datasource, 'Review');
    $collectionReviews->addFields(
        [
            'id'   => new ColumnSchema(columnType: PrimitiveType::UUID, filterOperators: [Operators::EQUAL], isPrimaryKey: true),
            'book' => new ManyToOneSchema(
                foreignKey: 'book_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'Book',
            ),
        ]
    );
    $datasource->addCollection($collectionBooks);
    $datasource->addCollection($collectionReviews);
    $datasource->addCollection($collectionBookReview);

    buildAgent($datasource);

    $searchCollection = new SearchCollection($collectionBookReview, $datasource);
    $filter = new Filter(search: '2d162303-78bf-599e-b197-93590ac3d315', searchExtended: true);

    expect($searchCollection->refineFilter($caller, $filter))->toEqual(
        new Filter(
            conditionTree: new ConditionTreeBranch(
                'Or',
                [
                    new ConditionTreeLeaf(field: 'id', operator: Operators::EQUAL, value: '2d162303-78bf-599e-b197-93590ac3d315'),
                    new ConditionTreeLeaf(field: 'book:id', operator: Operators::EQUAL, value: '2d162303-78bf-599e-b197-93590ac3d315'),
                    new ConditionTreeLeaf(field: 'review:id', operator: Operators::EQUAL, value: '2d162303-78bf-599e-b197-93590ac3d315'),
                ]
            ),
            search: null,
            searchExtended: true,
            segment: null
        ),
    );
})->with('caller');

test('refineFilter() should work when there is a replacer that use context in closure', function (Caller $caller) {
    [$datasource, $collection] = factorySearchCollection();
    $searchCollection = new SearchCollection($collection, $datasource);
    $filter = new Filter(search: 'something');
    $replace = fn ($value, $extended, CollectionCustomizationContext $context) =>  [
        'aggregator' => 'And',
        'conditions' => [
            ['field' => 'id', 'operator' => Operators::EQUAL, 'value' => $context->getCaller()->getId()],
            ['field' => 'foo', 'operator' => Operators::EQUAL, 'value' => $value],
        ],
    ];
    $searchCollection->replaceSearch($replace);

    expect($searchCollection->refineFilter($caller, $filter))->toEqual(
        new Filter(
            conditionTree: ConditionTreeFactory::fromArray(
                [
                    'aggregator' => 'And',
                    'conditions' => [
                        ['field' => 'id', 'operator' => Operators::EQUAL, 'value' => $caller->getId()],
                        ['field' => 'foo', 'operator' => Operators::EQUAL, 'value' => 'something'],
                    ],
                ]
            ),
            search: null,
            segment: null
        ),
    );
})->with('caller');
