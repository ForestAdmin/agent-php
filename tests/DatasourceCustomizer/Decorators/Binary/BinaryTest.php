<?php

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Binary\BinaryCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;

function factoryBinaryCollection()
{
    $datasource = new Datasource();
    $collectionFavorite = new Collection($datasource, 'Favorite');
    $collectionFavorite->addFields(
        [
            'id'     => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'book'   => new ManyToOneSchema(
                foreignKey: 'book_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'Book',
            ),
        ]
    );

    $collectionBook = new Collection($datasource, 'Book');
    $collectionBook->addFields(
        [
            'id'     => new ColumnSchema(columnType: PrimitiveType::BINARY, isPrimaryKey: true, validation: [
                ['operator' => Operators::LONGER_THAN, 'value' => 15],
                ['operator' => Operators::SHORTER_THAN, 'value' => 17],
                ['operator' => Operators::PRESENT],
                ['operator' => Operators::NOT_EQUAL, 'value' =>  bin2hex('123456')],
            ]),
            'title'  => new ColumnSchema(columnType: PrimitiveType::STRING),
            'cover'  => new ColumnSchema(columnType: PrimitiveType::BINARY),
//            'author' => new ColumnSchema(columnType: [
//                'name'    => PrimitiveType::STRING,
//                'picture' => PrimitiveType::BINARY,
//                'tags'    => [PrimitiveType::STRING],
//            ]),
        ]
    );

    $datasource->addCollection($collectionFavorite);
    $datasource->addCollection($collectionBook);
    buildAgent($datasource);

    $datasourceDecorator = new DatasourceDecorator($datasource, BinaryCollection::class);
    $datasourceDecorator->build();

    return [$datasourceDecorator, $collectionFavorite, $collectionBook];
}

test('setBinaryMode() should throw if an invalid mode is provided', function () {
    $datasourceDecorator = factoryBinaryCollection()[0];
    $decoratedBook = $datasourceDecorator->getCollection('Book');

    expect(fn () => $decoratedBook->setBinaryMode('cover', 'invalid'))->toThrow(\Exception::class, 'Invalid binary mode');
});

test('setBinaryMode() should throw if the field does not exist', function () {
    $datasourceDecorator = factoryBinaryCollection()[0];
    $decoratedBook = $datasourceDecorator->getCollection('Book');

    expect(fn () => $decoratedBook->setBinaryMode('invalid', 'hex'))->toThrow(\Exception::class, 'Undefined array key "invalid"');
});

test('setBinaryMode() should throw if the field is not a binary field', function () {
    $datasourceDecorator = factoryBinaryCollection()[0];
    $decoratedBook = $datasourceDecorator->getCollection('Book');

    expect(fn () => $decoratedBook->setBinaryMode('title', 'hex'))->toThrow(\Exception::class, 'Expected a binary field');
});

test('setBinaryMode() should not throw if the field is a binary field', function () {
    $datasourceDecorator = factoryBinaryCollection()[0];
    $decoratedBook = $datasourceDecorator->getCollection('Book');

    expect($decoratedBook->setBinaryMode('cover', 'hex'))->toBeNull();
});

test('favorite schema should not be modified', function () {
    [$datasourceDecorator, $favorite, $book] = factoryBinaryCollection();

    expect($favorite->getFields())->toEqual($datasourceDecorator->getCollection('Favorite')->getFields());
});

test('book primary key should be rewritten as an hex string', function () {
    $datasourceDecorator = factoryBinaryCollection()[0];
    $id = $datasourceDecorator->getCollection('Book')->getFields()['id'];

    expect($id->isPrimaryKey())->toBeTrue()
        ->and($id->getColumnType())->toEqual(PrimitiveType::STRING)
        ->and($id->getValidation())->toEqual(
            [
                ['operator' => Operators::MATCH, 'value' => '/^[0-9a-f]+$/'],
                ['operator' => Operators::LONGER_THAN, 'value' => 31],
                ['operator' => Operators::SHORTER_THAN, 'value' => 33],
                ['operator' => Operators::PRESENT],
            ]
        );
});

test('book author should be rewritten as a datauri', function () {
    $datasourceDecorator = factoryBinaryCollection()[0];
    $cover = $datasourceDecorator->getCollection('Book')->getFields()['cover'];

    expect($cover->getColumnType())->toEqual(PrimitiveType::STRING)
        ->and($cover->getValidation())->toEqual(
            [
                ['operator' => Operators::MATCH, 'value' => '/^data:.*;base64,.*/'],
            ]
        );
});

test('if requested, cover should be rewritten as a datauri', function () {
    $datasourceDecorator = factoryBinaryCollection()[0];
    $datasourceDecorator->getCollection('Book')->setBinaryMode('cover', 'datauri');
    $cover = $datasourceDecorator->getCollection('Book')->getFields()['cover'];

    expect($cover->getColumnType())->toEqual(PrimitiveType::STRING)
        ->and($cover->getValidation())->toEqual(
            [
                ['operator' => Operators::MATCH, 'value' => '/^data:.*;base64,.*/'],
            ]
        );
});

//  describe('list with a simple filter', () => {
//    // Build params (30303030 is the hex representation of 0000)
//    const conditionTree = new ConditionTreeLeaf('id', 'Equal', '30303030');
//    const caller = factories.caller.build();
//    const filter = new PaginatedFilter({ conditionTree });
//    const projection = new Projection('id', 'cover', 'author:picture');
//
//    let records: RecordData[];
//
//    beforeEach(async () => {
//      (books.list as jest.Mock).mockResolvedValue([bookRecord]);
//      records = await decoratedBook.list(caller, filter, projection);
//    });
//
//    test('query should be transformed', () => {
//      const expectedConditionTree = new ConditionTreeLeaf(
//        'id',
//        'Equal',
//        Buffer.from('0000', 'ascii'),
//      );
//
//      expect(books.list).toHaveBeenCalledWith(
//        caller,
//        expect.objectContaining({ conditionTree: expectedConditionTree }),
//        projection,
//      );
//    });

test('list with a simple filter - query should be transformed', function (Caller $caller) {
    $conditionTree = new ConditionTreeLeaf('id', Operators::EQUAL, '30303030');
    $filter = new PaginatedFilter(conditionTree: $conditionTree);
    $projection = new Projection(['id', 'cover']);

    [$datasourceDecorator, $favorite, $book] = factoryBinaryCollection();
    $expectedConditionTree = new ConditionTreeLeaf('id', Operators::EQUAL, bin2hex('30303030'));

    $decoratedBook = $datasourceDecorator->getCollection('Book');

    $childCollection = invokeProperty($decoratedBook, 'childCollection');
    $mock = mock($childCollection)
        ->makePartial()
        ->expects('list')
        ->once()
        ->withArgs(function ($caller, $filter) use ($expectedConditionTree) {
            return $filter->getConditionTree()->getValue() === hex2bin($expectedConditionTree->getValue());
        })
        ->andReturn([])
        ->getMock();

    invokeProperty($decoratedBook, 'childCollection', $mock);
    $decoratedBook->list($caller, $filter, $projection);
})->with('caller');

test('aaaaa', function (Caller $caller) {
    $conditionTree = new ConditionTreeLeaf('id', Operators::EQUAL, '30303030');
    $filter = new PaginatedFilter(conditionTree: $conditionTree);
    $projection = new Projection(['id', 'cover']);

    [$datasourceDecorator, $favorite, $book] = factoryBinaryCollection();

    $decoratedBook = $datasourceDecorator->getCollection('Book');


    $id = hex2bin('1234');
    $fp = fopen('php://temp', 'rb+');
    fwrite($fp, $id);
    fseek($fp, 0);
    $id = $fp;

    // TODO TEST FILE GET CONTENTS
    $cover = 'R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==';
    $a = file_get_contents(__DIR__ . 'transparent.gif');
    dd($a);
    $fp = fopen('php://temp', 'rb+');
    fwrite($fp, $cover);
    fseek($fp, 0);
    $cover = $fp;

    $records = [
        [
            'id'     => $id,
            'title'  => 'Foundation',
            'cover'  => $cover,
        ],
    ];

    $childCollection = invokeProperty($decoratedBook, 'childCollection');
    $mock = mock($childCollection)
        ->makePartial()
        ->shouldReceive('list')
        ->andReturn($records)
        ->getMock();

    invokeProperty($decoratedBook, 'childCollection', $mock);
    $decoratedBook->getFields();
    dd($decoratedBook->list($caller, $filter, $projection));
})->with('caller');
