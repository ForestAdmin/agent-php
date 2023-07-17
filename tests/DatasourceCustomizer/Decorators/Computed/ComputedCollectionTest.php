<?php

use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Context\CollectionCustomizationContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Computed\ComputedCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Computed\ComputedDefinition;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;
use ForestAdmin\AgentPHP\Tests\TestCase;

\Ozzie\Nest\describe('Computed collection', function () {
    $before = static function (TestCase $testCase, ?Aggregation $aggregation = null) {
        $datasource = new Datasource();
        $collectionBook = new Collection($datasource, 'Book');
        $collectionBook->addFields(
            [
                'id'       => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
                'authorId' => new ColumnSchema(columnType: PrimitiveType::STRING, isReadOnly: true, isSortable: true),
                'author'   => new ManyToOneSchema(
                    foreignKey: 'authorId',
                    foreignKeyTarget: 'id',
                    foreignCollection: 'Person',
                ),
                'title'    => new ColumnSchema(columnType: PrimitiveType::STRING),
            ]
        );

        $collectionPerson = new Collection($datasource, 'Person');
        $collectionPerson->addFields(
            [
                'id'        => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
                'firstName' => new ColumnSchema(columnType: PrimitiveType::STRING),
                'lastName'  => new ColumnSchema(columnType: PrimitiveType::STRING),
                'book'      => new OneToOneSchema(
                    originKey: 'authorId',
                    originKeyTarget: 'id',
                    foreignCollection: 'Book',
                ),
            ]
        );

        $records = [
            [
                'id'       => 1,
                'authorId' => 1,
                'author'   => ['id' => 1, 'firstName' => 'Isaac', 'lastName' => 'Asimov'],
                'title'    => 'Foundation',
            ],
            [
                'id'       => 2,
                'authorId' => 2,
                'author'   => ['id' => 2, 'firstName' => 'Edward O.', 'lastName' => 'Thorp'],
                'title'    => 'Beat the dealer',
            ],
        ];
        $collectionBook = mock($collectionBook)
            ->shouldReceive('list')
            ->andReturn($records);

        if ($aggregation) {
            $collectionBook->shouldReceive('aggregate')
                ->andReturn($aggregation->apply($records, 'Europe/Paris'));
        }


        $datasource->addCollection($collectionBook->getMock());
        $datasource->addCollection($collectionPerson);
        $testCase->buildAgent($datasource);

        $datasourceDecorator = new DatasourceDecorator($datasource, ComputedCollection::class);
        $datasourceDecorator->build();

        $datasourceDecorator->getCollection('Person')->registerComputed(
            'fullName',
            new ComputedDefinition(
                columnType: 'String',
                dependencies: ['firstName', 'lastName'],
                values: fn ($records) => collect($records)->map(fn ($record) => $record['firstName'] . ' ' . $record['lastName']),
            )
        );

        $testCase->bucket = compact('datasource', 'datasourceDecorator');
    };

    test('registerComputed() should throw if defining a field with no dependencies', closure: function () use ($before) {
        $before($this);
        $datasourceDecorator = $this->bucket['datasourceDecorator'];
        /** @var ComputedCollection $computedCollection */
        $computedCollection = $datasourceDecorator->getCollection('Book');

        expect(
            static fn () => $computedCollection->registerComputed(
                'newField',
                new ComputedDefinition(
                    columnType: 'String',
                    dependencies: [],
                    values: fn ($records) => $records,
                )
            )
        )->toThrow(ForestException::class, '🌳🌳🌳 Computed field Book.newField must have at least one dependency');
    });

    test('registerComputed() should throw if defining a field with missing dependencies', closure: function () use ($before) {
        $before($this);
        $datasourceDecorator = $this->bucket['datasourceDecorator'];
        /** @var ComputedCollection $computedCollection */
        $computedCollection = $datasourceDecorator->getCollection('Book');

        expect(
            static fn () => $computedCollection->registerComputed(
                'newField',
                new ComputedDefinition(
                    columnType: 'String',
                    dependencies: ['__nonExisting__'],
                    values: fn ($records) => $records,
                )
            )
        )->toThrow(ForestException::class, '🌳🌳🌳 Column not found: Book.__nonExisting__');
    });

    test('registerComputed() should throw if defining a field with invalid dependencies', closure: function () use ($before) {
        $before($this);
        $datasourceDecorator = $this->bucket['datasourceDecorator'];
        /** @var ComputedCollection $computedCollection */
        $computedCollection = $datasourceDecorator->getCollection('Book');

        expect(
            static fn () => $computedCollection->registerComputed(
                'newField',
                new ComputedDefinition(
                    columnType: 'String',
                    dependencies: ['author'],
                    values: fn ($records) => $records,
                )
            )
        )->toThrow(ForestException::class, "🌳🌳🌳 Unexpected field type: Book.author (found ManyToOne expected 'Column')");
    });

    test('getFields() should contain the computedField', closure: function () use ($before) {
        $before($this);
        $datasourceDecorator = $this->bucket['datasourceDecorator'];
        /** @var ComputedCollection $computedCollection */
        $computedCollection = $datasourceDecorator->getCollection('Person');

        expect($computedCollection->getFields())
            ->toHaveKey(
                'fullName',
                new ColumnSchema(columnType: PrimitiveType::STRING, isReadOnly: true)
            );
    });

    test('list() result should contain the computed that use context', closure: function (Caller $caller) use ($before) {
        $before($this);
        $datasourceDecorator = $this->bucket['datasourceDecorator'];
        $datasourceDecorator->getCollection('Person')->registerComputed(
            'firstNameWithContext',
            new ComputedDefinition(
                columnType: 'String',
                dependencies: ['firstName'],
                values: fn ($records, CollectionCustomizationContext $context) => collect($records)->map(fn ($record) => $record['firstName'] . '-' . $context->getCaller()->getId()),
            )
        );
        /** @var ComputedCollection $computedCollection */
        $computedCollection = $datasourceDecorator->getCollection('Book');
        $request = Request::createFromGlobals();
        $caller = QueryStringParser::parseCaller($request);
        $records = $computedCollection->list($caller, new PaginatedFilter(), new Projection(['title', 'author:firstNameWithContext']));

        expect($records)->toEqual(
            [
                ['title' => 'Foundation', 'author' => ['firstNameWithContext' => 'Isaac-1']],
                ['title' => 'Beat the dealer', 'author' => ['firstNameWithContext' => 'Edward O.-1' ]],
            ]
        );
    })->with('caller');

    test('list() result should contain the computed', closure: function () use ($before) {
        $before($this);
        $datasourceDecorator = $this->bucket['datasourceDecorator'];
        /** @var ComputedCollection $computedCollection */
        $computedCollection = $datasourceDecorator->getCollection('Book');
        $request = Request::createFromGlobals();
        $caller = QueryStringParser::parseCaller($request);
        $records = $computedCollection->list($caller, new PaginatedFilter(), new Projection(['title', 'author:fullName']));

        expect($records)->toEqual(
            [
                ['title' => 'Foundation', 'author' => ['fullName' => 'Isaac Asimov']],
                ['title' => 'Beat the dealer', 'author' => ['fullName' => 'Edward O. Thorp']],
            ]
        );
    });

    test('aggregate() aggregate() should use the child implementation when relevant', closure: function () use ($before) {
        $aggregate = new Aggregation(operation: 'Count');
        $before($this, $aggregate);
        $datasourceDecorator = $this->bucket['datasourceDecorator'];
        //['datasourceDecorator' => $datasourceDecorator] = factoryComputedCollection($aggregate);
        /** @var ComputedCollection $computedCollection */
        $computedCollection = $datasourceDecorator->getCollection('Book');
        $request = Request::createFromGlobals();
        $caller = QueryStringParser::parseCaller($request);
        $rows = $computedCollection->aggregate($caller, new Filter(), $aggregate);

        expect($rows)->toBeArray()
            ->and($rows[0])->toBeArray()
            ->and($rows[0])->toHaveKey('value', 2)
            ->and($rows[0])->toHaveKey('group', []);
    });

    test('aggregate() should work with computed', closure: function () use ($before) {
        $aggregate = new Aggregation(operation: 'Min', field: 'author:fullName');
        $before($this, $aggregate);
        $datasourceDecorator = $this->bucket['datasourceDecorator'];
        /** @var ComputedCollection $computedCollection */
        $computedCollection = $datasourceDecorator->getCollection('Book');
        $request = Request::createFromGlobals();
        $caller = QueryStringParser::parseCaller($request);
        $rows = $computedCollection->aggregate($caller, new Filter(), $aggregate);

        expect($rows)->toBeArray()
            ->and($rows[0])->toBeArray()
            ->and($rows[0])->toHaveKey('value', 'Edward O. Thorp')
            ->and($rows[0])->toHaveKey('group', []);
    });
});
