<?php

namespace ForestAdmin\AgentPHP\DatasourceDummy\Collections;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ActionSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\ActionScope;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\ManyToOneSchema;

class Book extends BaseCollection
{
    public function __construct(DatasourceContract $dataSource)
    {
        $fields = [
            'id' => new ColumnSchema(
                columnType: PrimitiveType::Number(),
                isPrimaryKey: true
            ),
            'title' => new ColumnSchema(
                columnType: PrimitiveType::String(),
                defaultValue: 'Foo'
            ),
            'publication' => new ColumnSchema(
                columnType: PrimitiveType::Date(),
            ),
            'authorId' => new ColumnSchema(
                columnType: PrimitiveType::Number(),
            ),
            'author' => new ManyToOneSchema( // TODO CHECK IT'S GOOD ?
                foreignKey: 'authorId',
                foreignKeyTarget: 'id',
                foreignCollection: 'Person'
            ),
        ];
        parent::__construct($dataSource, 'Book', $fields);

        $this->dataSource = $dataSource;

        $this->addAction('Mark as live', new ActionSchema(scope: ActionScope::single(), staticForm: true));

        $this->addSegments(['Active books', 'Deleted books']);

        $this->setSearchable(true);
    }
}
