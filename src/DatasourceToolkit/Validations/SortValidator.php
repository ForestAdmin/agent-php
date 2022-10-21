<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Validations;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Sort;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

class SortValidator
{
    /**
     * @throws ForestException
     */
    public static function validate(CollectionContract $collection, Sort $sort): void
    {
        foreach ($sort->getFields() as $field) {
            FieldValidator::validate($collection, $field['field']);
        }
    }
}
