<?php

use ForestAdmin\AgentPHP\DatasourceCustomizer\Context\RelaxedWrapper\RelaxedCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Computed\ComputedCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use Mockery;

describe('RelaxedCollection', function () {
    beforeEach(function () {
        $datasource = new Datasource();
        $collectionBook = new Collection($datasource, 'Book');
        $collectionBook->addFields(
            [
                'id'    => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true, isReadOnly: true),
                'title' => new ColumnSchema(columnType: PrimitiveType::STRING, filterOperators: [Operators::LONGER_THAN, Operators::PRESENT]),
            ]
        );

        $collectionBook = Mockery::mock($collectionBook)
            ->shouldReceive('getNativeDriver')
            ->andReturn('a native driver');

        $datasource->addCollection($collectionBook->getMock());
        $this->buildAgent($datasource);

        $datasourceDecorator = new DatasourceDecorator($datasource, ComputedCollection::class);
        $datasourceDecorator->build();

        $this->bucket['bookCollection'] = $datasourceDecorator->getCollection('Book');
    });

    test('getNativeDriver should work', function (Caller $caller) {
        $collectionBook = $this->bucket['bookCollection'];
        $relaxedCollection = new RelaxedCollection($collectionBook, $caller);

        expect($relaxedCollection->getNativeDriver())->toEqual('a native driver');

    })->with('caller');
});
