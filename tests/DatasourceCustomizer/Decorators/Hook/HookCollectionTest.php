<?php

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context\After\HookAfterAggregateContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context\After\HookAfterCreateContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context\After\HookAfterDeleteContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context\After\HookAfterListContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context\After\HookAfterUpdateContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context\Before\HookBeforeAggregateContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context\Before\HookBeforeCreateContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context\Before\HookBeforeDeleteContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context\Before\HookBeforeListContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context\Before\HookBeforeUpdateContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\HookCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Hooks;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\Tests\TestCase;

describe('Computed collection', function () {
    $before = static function (TestCase $testCase) {
        $records = [['id' => 1, 'description' => 'new transaction', 'amount' => 100]];
        $aggregateResult = [['value' => 1, 'group' => []]];
        $datasource = new Datasource();
        $collectionTransactions = new Collection($datasource, 'Transaction');
        $collectionTransactions->addFields(
            [
                'id'             => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
                'description'    => new ColumnSchema(columnType: PrimitiveType::STRING),
                'amount'         => new ColumnSchema(columnType: PrimitiveType::NUMBER),
            ]
        );

        $collectionTransactions = \Mockery::mock($collectionTransactions)
            ->shouldReceive('list')
            ->andReturn($records)
            ->shouldReceive('create')
            ->andReturn($records[0])
            ->shouldReceive('aggregate')
            ->andReturn($aggregateResult)
            ->getMock();

        $datasource->addCollection($collectionTransactions);
        $testCase->buildAgent($datasource);

        $datasourceDecorator = new DatasourceDecorator($datasource, HookCollection::class);
        $datasourceDecorator->build();

        $decoratedTransaction = $datasourceDecorator->getCollection('Transaction');
        $testCase->bucket = compact('datasource', 'datasourceDecorator', 'collectionTransactions', 'decoratedTransaction', 'records', 'aggregateResult');
    };

    test('schema should not be changed', function () use ($before) {
        $before($this);
        $collectionTransactions = $this->bucket['collectionTransactions'];
        /** @var HookCollection $decoratedTransaction */
        $decoratedTransaction = $this->bucket['decoratedTransaction'];

        expect($collectionTransactions->getFields())->toEqual($decoratedTransaction->getFields());
    });

    test('when adding a before hook on a list it should call the hook with valid parameters', function ($caller) use ($before) {
        $before($this);
        /** @var HookCollection $decoratedTransaction */
        $decoratedTransaction = $this->bucket['decoratedTransaction'];
        $mock = \Mockery::mock(fn () => true);
        $hook = new Hooks();
        $this->invokeProperty($hook, 'before', [$mock]);
        $this->invokeProperty($decoratedTransaction, 'hooks', ['List' => $hook]);
        $filter = new PaginatedFilter();
        $projection = new Projection();
        $context = new HookBeforeListContext($decoratedTransaction, $caller, $filter, $projection);

        $mock->expects('__invoke')
            ->once()
            ->withArgs(function (HookBeforeListContext $mockContext) use ($context) {
                return $mockContext->getCaller() === $context->getCaller()
                    && $mockContext->getFilter() === $context->getFilter()
                    && $mockContext->getProjection() === $context->getProjection();
            });

        $decoratedTransaction->list($caller, $filter, $projection);
    })->with('caller');

    test('when adding a before hook on a create it should call the hook with valid parameters', function ($caller) use ($before) {
        $before($this);
        /** @var HookCollection $decoratedTransaction */
        $decoratedTransaction = $this->bucket['decoratedTransaction'];
        $mock = \Mockery::mock(fn () => true);
        $hook = new Hooks();
        $this->invokeProperty($hook, 'before', [$mock]);
        $this->invokeProperty($decoratedTransaction, 'hooks', ['Create' => $hook]);
        $data = ['description' => 'new transaction', 'amount' => 100];
        $context = new HookBeforeCreateContext($decoratedTransaction, $caller, $data);

        $mock->expects('__invoke')
            ->once()
            ->withArgs(function (HookBeforeCreateContext $mockContext) use ($context) {
                return $mockContext->getCaller() === $context->getCaller()
                    && $mockContext->getData() === $context->getData();
            });

        $decoratedTransaction->create($caller, $data);
    })->with('caller');

    test('when adding a before hook on a update it should call the hook with valid parameters', function ($caller) use ($before) {
        $before($this);
        /** @var HookCollection $decoratedTransaction */
        $decoratedTransaction = $this->bucket['decoratedTransaction'];
        $mock = \Mockery::mock(fn () => true);
        $hook = new Hooks();
        $this->invokeProperty($hook, 'before', [$mock]);
        $this->invokeProperty($decoratedTransaction, 'hooks', ['Update' => $hook]);
        $data = ['description' => 'new transaction', 'amount' => 100];
        $filter = new Filter(conditionTree: new ConditionTreeLeaf('id', Operators::EQUAL, 1));
        $context = new HookBeforeUpdateContext($decoratedTransaction, $caller, $filter, $data);

        $mock->expects('__invoke')
            ->once()
            ->withArgs(function (HookBeforeUpdateContext $mockContext) use ($context) {
                return $mockContext->getCaller() === $context->getCaller()
                    && $mockContext->getFilter() === $context->getFilter()
                    && $mockContext->getPatch() === $context->getPatch();
            });

        $decoratedTransaction->update($caller, $filter, $data);
    })->with('caller');

    test('when adding a before hook on a delete it should call the hook with valid parameters', function ($caller) use ($before) {
        $before($this);
        /** @var HookCollection $decoratedTransaction */
        $decoratedTransaction = $this->bucket['decoratedTransaction'];
        $mock = \Mockery::mock(fn () => true);
        $hook = new Hooks();
        $this->invokeProperty($hook, 'before', [$mock]);
        $this->invokeProperty($decoratedTransaction, 'hooks', ['Delete' => $hook]);
        $filter = new Filter(conditionTree: new ConditionTreeLeaf('id', Operators::EQUAL, 1));
        $context = new HookBeforeDeleteContext($decoratedTransaction, $caller, $filter);

        $mock->expects('__invoke')
            ->once()
            ->withArgs(function (HookBeforeDeleteContext $mockContext) use ($context) {
                return $mockContext->getCaller() === $context->getCaller()
                    && $mockContext->getFilter() === $context->getFilter();
            });

        $decoratedTransaction->delete($caller, $filter);
    })->with('caller');

    test('when adding a before hook on a aggregate it should call the hook with valid parameters', function ($caller) use ($before) {
        $before($this);
        /** @var HookCollection $decoratedTransaction */
        $decoratedTransaction = $this->bucket['decoratedTransaction'];
        $mock = \Mockery::mock(fn () => true);
        $hook = new Hooks();
        $this->invokeProperty($hook, 'before', [$mock]);
        $this->invokeProperty($decoratedTransaction, 'hooks', ['Aggregate' => $hook]);
        $filter = new PaginatedFilter();
        $aggregation = new Aggregation('Count');
        $context = new HookBeforeAggregateContext($decoratedTransaction, $caller, $filter, $aggregation);

        $mock->expects('__invoke')
            ->once()
            ->withArgs(function (HookBeforeAggregateContext $mockContext) use ($context) {
                return $mockContext->getCaller() === $context->getCaller()
                    && $mockContext->getFilter() === $context->getFilter()
                    && $mockContext->getAggregation() === $context->getAggregation();
            });

        $decoratedTransaction->aggregate($caller, $filter, $aggregation);
    })->with('caller');

    test('when adding a after hook on a list it should call the hook with valid parameters', function ($caller) use ($before) {
        $before($this);
        /** @var HookCollection $decoratedTransaction */
        $decoratedTransaction = $this->bucket['decoratedTransaction'];
        $records = $this->bucket['records'];
        $mock = \Mockery::mock(fn () => true);
        $hook = new Hooks();
        $this->invokeProperty($hook, 'after', [$mock]);
        $this->invokeProperty($decoratedTransaction, 'hooks', ['List' => $hook]);
        $filter = new PaginatedFilter();
        $projection = new Projection();
        $context = new HookAfterListContext($decoratedTransaction, $caller, $filter, $projection, $records);

        $mock->expects('__invoke')
            ->once()
            ->withArgs(function (HookAfterListContext $mockContext) use ($context) {
                return $mockContext->getCaller() === $context->getCaller()
                    && $mockContext->getFilter() === $context->getFilter()
                    && $mockContext->getProjection() === $context->getProjection()
                    && $mockContext->getRecords() === $context->getRecords();
            });

        expect($decoratedTransaction->list($caller, $filter, $projection))->toEqual($records);
    })->with('caller');

    test('when adding a after hook on a create it should call the hook with valid parameters', function ($caller) use ($before) {
        $before($this);
        /** @var HookCollection $decoratedTransaction */
        $decoratedTransaction = $this->bucket['decoratedTransaction'];
        $records = $this->bucket['records'];
        $mock = \Mockery::mock(fn () => true);
        $hook = new Hooks();
        $this->invokeProperty($hook, 'after', [$mock]);
        $this->invokeProperty($decoratedTransaction, 'hooks', ['Create' => $hook]);
        $data = ['description' => 'new transaction', 'amount' => 100];
        $context = new HookAfterCreateContext($decoratedTransaction, $caller, $data, $records[0]);

        $mock->expects('__invoke')
            ->once()
            ->withArgs(function (HookAfterCreateContext $mockContext) use ($context) {
                return $mockContext->getCaller() === $context->getCaller()
                    && $mockContext->getData() === $context->getData()
                    && $mockContext->getRecord() === $context->getRecord();
            });

        expect($decoratedTransaction->create($caller, $data))->toEqual($records[0]);
    })->with('caller');

    test('when adding a after hook on a update it should call the hook with valid parameters', function ($caller) use ($before) {
        $before($this);
        /** @var HookCollection $decoratedTransaction */
        $decoratedTransaction = $this->bucket['decoratedTransaction'];
        $mock = \Mockery::mock(fn () => true);
        $hook = new Hooks();
        $this->invokeProperty($hook, 'after', [$mock]);
        $this->invokeProperty($decoratedTransaction, 'hooks', ['Update' => $hook]);
        $data = ['description' => 'new transaction', 'amount' => 100];
        $filter = new Filter(conditionTree: new ConditionTreeLeaf('id', Operators::EQUAL, 1));
        $context = new HookAfterUpdateContext($decoratedTransaction, $caller, $filter, $data);

        $mock->expects('__invoke')
            ->once()
            ->withArgs(function (HookAfterUpdateContext $mockContext) use ($context) {
                return $mockContext->getCaller() === $context->getCaller()
                    && $mockContext->getFilter() === $context->getFilter()
                    && $mockContext->getPatch() === $context->getPatch();
            });

        $decoratedTransaction->update($caller, $filter, $data);
    })->with('caller');

    test('when adding a after hook on a delete it should call the hook with valid parameters', function ($caller) use ($before) {
        $before($this);
        /** @var HookCollection $decoratedTransaction */
        $decoratedTransaction = $this->bucket['decoratedTransaction'];
        $mock = \Mockery::mock(fn () => true);
        $hook = new Hooks();
        $this->invokeProperty($hook, 'after', [$mock]);
        $this->invokeProperty($decoratedTransaction, 'hooks', ['Delete' => $hook]);
        $filter = new Filter(conditionTree: new ConditionTreeLeaf('id', Operators::EQUAL, 1));
        $context = new HookAfterDeleteContext($decoratedTransaction, $caller, $filter);

        $mock->expects('__invoke')
            ->once()
            ->withArgs(function (HookAfterDeleteContext $mockContext) use ($context) {
                return $mockContext->getCaller() === $context->getCaller()
                    && $mockContext->getFilter() === $context->getFilter();
            });

        $decoratedTransaction->delete($caller, $filter);
    })->with('caller');

    test('when adding a after hook on a aggregate it should call the hook with valid parameters', function ($caller) use ($before) {
        $before($this);
        /** @var HookCollection $decoratedTransaction */
        $decoratedTransaction = $this->bucket['decoratedTransaction'];
        $aggregateResult = $this->bucket['aggregateResult'];
        $mock = \Mockery::mock(fn () => true);
        $hook = new Hooks();
        $this->invokeProperty($hook, 'after', [$mock]);
        $this->invokeProperty($decoratedTransaction, 'hooks', ['Aggregate' => $hook]);
        $filter = new PaginatedFilter();
        $aggregation = new Aggregation('Count');
        $context = new HookAfterAggregateContext($decoratedTransaction, $caller, $filter, $aggregation, $aggregateResult);

        $mock->expects('__invoke')
            ->once()
            ->withArgs(function (HookAfterAggregateContext $mockContext) use ($context) {
                return $mockContext->getCaller() === $context->getCaller()
                    && $mockContext->getFilter() === $context->getFilter()
                    && $mockContext->getAggregation() === $context->getAggregation()
                    && $mockContext->getAggregateResult() === $context->getAggregateResult();
            });

        expect($decoratedTransaction->aggregate($caller, $filter, $aggregation))->toEqual($aggregateResult);
    })->with('caller');
});
