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
                columnType: PrimitiveType::NUMBER,
                isPrimaryKey: true
            ),
            'title' => new ColumnSchema(
                columnType: PrimitiveType::STRING,
                defaultValue: 'Foo'
            ),
            'publication' => new ColumnSchema(
                columnType: PrimitiveType::DATE,
            ),
            'authorId' => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
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

        $this->addSegment('Active books');
        $this->addSegment('Deleted books');

        $this->setSearchable(true);
    }
}
