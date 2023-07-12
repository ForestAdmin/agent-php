<?php

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\CollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

\Ozzie\Nest\describe('CollectionDecorator', function () {
    beforeEach(function () {
        $datasource = new Datasource();
        $collectionProduct = new Collection($datasource, 'Product');
        $collectionProduct->addFields(
            [
                'id'    => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
                'name'  => new ColumnSchema(columnType: PrimitiveType::STRING, filterOperators: [Operators::EQUAL, Operators::IN]),
                'price' => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::EQUAL, Operators::GREATER_THAN, Operators::LESS_THAN]),
            ]
        );

        $datasource->addCollection($collectionProduct);
        $this->buildAgent($datasource);
        $collectionDecorator = new CollectionDecorator($collectionProduct, $datasource);

        $this->bucket['collectionDecorator'] = $collectionDecorator;
    });

    test('isSearchable() should call the childCollection corresponding method', function () {
        $collectionDecorator = $this->bucket['collectionDecorator'];

        $childCollection = invokeProperty($collectionDecorator, 'childCollection');
        $childCollection = mock($childCollection)
            ->shouldReceive('isSearchable')
            ->once()
            ->andReturn(false)
            ->getMock();

        invokeProperty($collectionDecorator, 'childCollection', $childCollection);

        expect($collectionDecorator->isSearchable())->toBeFalse();
    });

    test('getFields() should call the childCollection corresponding method', function () {
        $collectionDecorator = $this->bucket['collectionDecorator'];

        $childCollection = invokeProperty($collectionDecorator, 'childCollection');
        $childCollection = mock($childCollection)
            ->shouldReceive('getFields')
            ->once()
            ->andReturn(collect())
            ->getMock();

        invokeProperty($collectionDecorator, 'childCollection', $childCollection);

        expect($collectionDecorator->getFields())->toEqual(collect());
    });

    test('execute() should call the childCollection corresponding method', function (Caller $caller) {
        $collectionDecorator = $this->bucket['collectionDecorator'];

        $childCollection = invokeProperty($collectionDecorator, 'childCollection');
        $childCollection = mock($childCollection)
            ->shouldReceive('execute')
            ->once()
            ->getMock();

        invokeProperty($collectionDecorator, 'childCollection', $childCollection);
        $collectionDecorator->execute($caller, 'foo', []);

        expect($collectionDecorator->getFields()->toArray())->toHaveKeys(['id', 'name', 'price']);
    })->with('caller');

    test('getForm() should call the childCollection corresponding method', function (Caller $caller) {
        $collectionDecorator = $this->bucket['collectionDecorator'];

        $childCollection = invokeProperty($collectionDecorator, 'childCollection');
        $childCollection = mock($childCollection)
            ->shouldReceive('getForm')
            ->once()
            ->getMock();

        invokeProperty($collectionDecorator, 'childCollection', $childCollection);
        $collectionDecorator->getForm($caller, 'foo', []);

        expect($collectionDecorator->getFields()->toArray())->toHaveKeys(['id', 'name', 'price']);
    })->with('caller');

    test('create() should call the childCollection corresponding method', function (Caller $caller) {
        $collectionDecorator = $this->bucket['collectionDecorator'];

        $childCollection = invokeProperty($collectionDecorator, 'childCollection');
        $childCollection = mock($childCollection)
            ->shouldReceive('create')
            ->once()
            ->andReturn(['id' => 1, 'name' => 'foo', 'price' => 1000])
            ->getMock();
        invokeProperty($collectionDecorator, 'childCollection', $childCollection);

        expect($collectionDecorator->create($caller, []))->toEqual(['id' => 1, 'name' => 'foo', 'price' => 1000]);
    })->with('caller');

    test('list() should call the childCollection corresponding method', function (Caller $caller) {
        $collectionDecorator = $this->bucket['collectionDecorator'];

        $childCollection = invokeProperty($collectionDecorator, 'childCollection');
        $childCollection = mock($childCollection)
            ->shouldReceive('list')
            ->once()
            ->andReturn([
                ['id' => 1, 'name' => 'foo', 'price' => 1000],
                ['id' => 2, 'name' => 'foo2', 'price' => 1500],
                ['id' => 3, 'name' => 'foo3', 'price' => 2000],
            ])
            ->getMock();
        invokeProperty($collectionDecorator, 'childCollection', $childCollection);

        expect($collectionDecorator->list($caller, new PaginatedFilter(), new Projection()))->toEqual([
            ['id' => 1, 'name' => 'foo', 'price' => 1000],
            ['id' => 2, 'name' => 'foo2', 'price' => 1500],
            ['id' => 3, 'name' => 'foo3', 'price' => 2000],
        ]);
    })->with('caller');

    test('update() should call the childCollection corresponding method', function (Caller $caller) {
        $collectionDecorator = $this->bucket['collectionDecorator'];

        $childCollection = invokeProperty($collectionDecorator, 'childCollection');
        $childCollection = mock($childCollection)
            ->shouldReceive('update')
            ->once()
            ->getMock();
        invokeProperty($collectionDecorator, 'childCollection', $childCollection);

        expect($collectionDecorator->update($caller, new Filter(), ['id' => 1]))->toBeNull();
    })->with('caller');

    test('delete() should call the childCollection corresponding method', function (Caller $caller) {
        $collectionDecorator = $this->bucket['collectionDecorator'];

        $childCollection = invokeProperty($collectionDecorator, 'childCollection');
        $childCollection = mock($childCollection)
            ->shouldReceive('delete')
            ->once()
            ->getMock();
        invokeProperty($collectionDecorator, 'childCollection', $childCollection);

        expect($collectionDecorator->delete($caller, new Filter()))->toBeNull();
    })->with('caller');

    test('aggregate() should call the childCollection corresponding method', function (Caller $caller) {
        $collectionDecorator = $this->bucket['collectionDecorator'];

        $childCollection = invokeProperty($collectionDecorator, 'childCollection');
        $childCollection = mock($childCollection)
            ->shouldReceive('aggregate')
            ->once()
            ->getMock();
        invokeProperty($collectionDecorator, 'childCollection', $childCollection);

        expect($collectionDecorator->aggregate($caller, new Filter(), new Aggregation(operation: 'Count')))->toBeNull();
    })->with('caller');

    test('makeTransformer() should call the childCollection corresponding method', function () {
        $collectionDecorator = $this->bucket['collectionDecorator'];

        $childCollection = invokeProperty($collectionDecorator, 'childCollection');
        $childCollection = mock($childCollection)
            ->shouldReceive('makeTransformer')
            ->once()
            ->getMock();
        invokeProperty($collectionDecorator, 'childCollection', $childCollection);

        expect($collectionDecorator->makeTransformer())->toBeNull();
    });

});
