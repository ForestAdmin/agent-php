<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Validations;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;

class ProjectionValidator
{
    public static function validate(CollectionContract $collection, Projection $projection): void
    {
        collect($projection)->each(fn ($field) => FieldValidator::validate($collection, $field));
    }
}
