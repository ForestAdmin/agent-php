<?php


use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Computed\ComputedCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Computed\Utils\ComputeField;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;

test('transformUniqueValues() should work', function () {
    $inputs = [1, null, 2, 2, null, 666];
    $result = ComputeField::transformUniqueValues(
        $inputs,
        fn ($item) => collect($item)->map(fn ($value) => $value * 2)
    );

    expect($result)->toEqual([2, null, 4, 4, null, 1332]);
});

//test('computeFromRecords() should work', function () {
//    $datasource = new Datasource();
//    $collectionBook = new Collection($datasource, 'Book');
//    $collectionBook->addFields(
//        [
//            'id'           => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
//            'authorId'     => new ColumnSchema(columnType: PrimitiveType::STRING, isReadOnly: true, isSortable: true),
//            'author'       => new ManyToOneSchema(
//                foreignKey: 'authorId',
//                foreignKeyTarget: 'id',
//                foreignCollection: 'Person',
//            ),
//        ]
//    );
//    $computedCollection = new ComputedCollection($collectionBook, $datasource);
//
//    dd($computedCollection);
//
//
//    $inputs = [1, null, 2, 2, null, 666];
//    $result = ComputeField::computeFromRecords($computedCollection,)
//
//    expect($result)->toEqual([2, null, 4, 4, null, 1332]);
//});
