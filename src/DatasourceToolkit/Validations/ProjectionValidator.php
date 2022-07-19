<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Validations;

use Exception;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;

class ProjectionValidator
{
    /**
     * @throws Exception
     */
    public static function validate(Collection $collection, Projection $projection): void
    {
        collect($projection)->each(fn ($field) => FieldValidator::validate($collection, $field));
    }
}
