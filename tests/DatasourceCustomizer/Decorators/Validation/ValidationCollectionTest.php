<?php

namespace ForestAdmin\AgentPHP\Tests\DatasourceCustomizer\Decorators\Schema;

use ForestAdmin\AgentPHP\Agent\Http\Exceptions\ValidationError;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Validation\ValidationCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;

\Ozzie\Nest\describe('ValidationCollection', function () {
    beforeEach(function () {
        $datasource = new Datasource();
        $collectionBook = new Collection($datasource, 'Book');
        $collectionBook->addFields(
            [
                'id'           => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true, isReadOnly: true),
                'authorId'     => new ColumnSchema(columnType: PrimitiveType::STRING, isReadOnly: true, isSortable: true),
                'author'       => new ManyToOneSchema(
                    foreignKey: 'authorId',
                    foreignKeyTarget: 'id',
                    foreignCollection: 'Person',
                ),
                'title'        => new ColumnSchema(columnType: PrimitiveType::STRING, filterOperators: [Operators::LONGER_THAN, Operators::PRESENT]),
                'sub_title'    => new ColumnSchema(columnType: PrimitiveType::STRING, filterOperators: [Operators::LONGER_THAN]),
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

        $datasource->addCollection($collectionBook);
        $datasource->addCollection($collectionPerson);
        $this->buildAgent($datasource);

        $datasourceDecorator = new DatasourceDecorator($datasource, ValidationCollection::class);
        $datasourceDecorator->build();

        /** @var ValidationCollection $newBooks */
        $newBooks = $datasourceDecorator->getCollection('Book');

        $this->bucket['newBooks'] = $newBooks;
    });


    test('addValidation() should throw if the field does not exists', function () {
        $newBooks = $this->bucket['newBooks'];

        expect(fn () => $newBooks->addValidation('__dontExist', ['operator' => Operators::PRESENT]))
            ->toThrow(ForestException::class, '🌳🌳🌳 Column not found: Book.__dontExist');
    });

    test('addValidation() should throw if the field is readonly', function () {
        $newBooks = $this->bucket['newBooks'];

        expect(fn () => $newBooks->addValidation('id', ['operator' => Operators::PRESENT]))
            ->toThrow(ForestException::class, '🌳🌳🌳 Cannot add validators on a readonly field');
    });

    test('addValidation() should throw if the field is a relation', function () {
        $newBooks = $this->bucket['newBooks'];

        expect(fn () => $newBooks->addValidation('author', ['operator' => Operators::PRESENT]))
            ->toThrow(ForestException::class, "🌳🌳🌳 Unexpected field type: Book.author (found ManyToOne expected 'Column')");
    });

    test('addValidation() should throw if the field is in a relation', function () {
        $newBooks = $this->bucket['newBooks'];

        expect(fn () => $newBooks->addValidation('author:firstName', ['operator' => Operators::PRESENT]))
            ->toThrow(ForestException::class, '🌳🌳🌳 Cannot add validators on a relation, use the foreign key instead');
    });

    test('Field selection when validating - should validate all fields when creating a record', function (Caller $caller) {
        $newBooks = $this->bucket['newBooks'];
        $newBooks->addValidation('title', ['operator' => Operators::LONGER_THAN, 'value' => 5]);
        $newBooks->addValidation('sub_title', ['operator' => Operators::LONGER_THAN, 'value' => 5]);

        expect(fn () => $newBooks->create($caller, ['title' => 'longtitle', 'sub_title' => '']))
            ->toThrow(ValidationError::class, 'sub_title failed validation rule : Longer_Than(5)');
    })->with('caller');

    test('Field selection when validating - should validate only changed fields when updating', function (Caller $caller) {
        $newBooks = $this->bucket['newBooks'];
        $newBooks->addValidation('title', ['operator' => Operators::LONGER_THAN, 'value' => 5]);
        $newBooks->addValidation('sub_title', ['operator' => Operators::LONGER_THAN, 'value' => 5]);

        expect($newBooks->update($caller, new Filter(), ['title' => 'longtitle']))->toBeNull();
    })->with('caller');

    test('Validation when setting to null (null allowed) - should forward create that respect the rule', function (Caller $caller) {
        $newBooks = $this->bucket['newBooks'];
        $newBooks->addValidation('title', ['operator' => Operators::LONGER_THAN, 'value' => 5]);

        expect($newBooks->create($caller, ['title' => null]))->toBeNull();
    })->with('caller');

    test('Validation when setting to null (null allowed) - should forward updates that respect the rule', function (Caller $caller) {
        $newBooks = $this->bucket['newBooks'];
        $newBooks->addValidation('title', ['operator' => Operators::LONGER_THAN, 'value' => 5]);

        expect($newBooks->update($caller, new Filter(), ['title' => null]))->toBeNull();
    })->with('caller');

    test('Validation when setting to null (null forbidden) - should not forward create that respect the rule', function (Caller $caller) {
        $newBooks = $this->bucket['newBooks'];
        $newBooks->addValidation('title', ['operator' => Operators::LONGER_THAN, 'value' => 5]);
        $newBooks->addValidation('title', ['operator' => Operators::PRESENT]);

        expect(fn () => $newBooks->create($caller, ['title' => null]))->toThrow(ValidationError::class, "title failed validation rule : Present");
    })->with('caller');

    test('Validation when setting to null (null forbidden) - should not forward updates that respect the rule', function (Caller $caller) {
        $newBooks = $this->bucket['newBooks'];
        $newBooks->addValidation('title', ['operator' => Operators::LONGER_THAN, 'value' => 5]);
        $newBooks->addValidation('title', ['operator' => Operators::PRESENT]);

        expect(fn () => $newBooks->update($caller, new Filter(), ['title' => null]))->toThrow(ValidationError::class, "title failed validation rule : Present");
    })->with('caller');

    test('Validation on a defined value - should forward create that respect the rule', function (Caller $caller) {
        $newBooks = $this->bucket['newBooks'];
        $newBooks->addValidation('title', ['operator' => Operators::LONGER_THAN, 'value' => 5]);

        expect($newBooks->create($caller, ['title' => '123456']))->toBeNull();
    })->with('caller');

    test('Validation on a defined value - should forward updates that respect the rule', function (Caller $caller) {
        $newBooks = $this->bucket['newBooks'];
        $newBooks->addValidation('title', ['operator' => Operators::LONGER_THAN, 'value' => 5]);

        expect($newBooks->update($caller, new Filter(), ['title' => '123456']))->toBeNull();
    })->with('caller');

    test('Validation on a defined value - should reject create that do not respect the rule', function (Caller $caller) {
        $newBooks = $this->bucket['newBooks'];
        $newBooks->addValidation('title', ['operator' => Operators::LONGER_THAN, 'value' => 5]);

        expect(fn () => $newBooks->create($caller, ['title' => '1234']))->toThrow(ValidationError::class, 'title failed validation rule : Longer_Than(5)');
    })->with('caller');

    test('Validation on a defined value - should reject updates that do not respect the rule', function (Caller $caller) {
        $newBooks = $this->bucket['newBooks'];
        $newBooks->addValidation('title', ['operator' => Operators::LONGER_THAN, 'value' => 5]);

        expect(fn () => $newBooks->update($caller, new Filter(), ['title' => '1234']))->toThrow(ValidationError::class, 'title failed validation rule : Longer_Than(5)');
    })->with('caller');

});
