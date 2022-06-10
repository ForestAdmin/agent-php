<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Utils;

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\RelationSchema;

class Schema
{
    public static function getPrimaryKeys(Collection $schema): array
    {
        return $schema
            ->getFields()
            ->keys()
            ->filter(
                static function ($fieldName) use ($schema) {
                    /** @var ColumnSchema|RelationSchema $field */
                    $field = $schema->getFields()[$fieldName];

                    return $field->getType() === 'Column' && $field->isPrimaryKey();
                }
            );
    }

}
