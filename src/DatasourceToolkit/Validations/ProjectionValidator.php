<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Validations;

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;

class ProjectionValidator
{
    public static function validate(Collection $collection, Projection $projection): void
    {
        collect($projection)->each(fn ($field) => FieldValidator::validate($collection, $field));
    }
}
