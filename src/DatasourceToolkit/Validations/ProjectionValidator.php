<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Validations;

use Exception;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use Illuminate\Support\Collection as IlluminateCollection;

class ProjectionValidator
{
    /**
     * @throws Exception
     */
    public static function validate(Collection $collection, IlluminateCollection $projection): void
    {
        $projection->each(fn ($field) => FieldValidator::validate($collection, $field));
    }
}
