<?php

namespace ForestAdmin\AgentPHP\Agent\Utils\Traits;

use Illuminate\Support\Str;

trait FormatQuery
{
    public function formatField($collection, string $field): string
    {
        if (Str::contains($field, ':')) {
            $relation = $collection->getFields()[Str::before($field, ':')];
            $tableName = $collection
                ->getDataSource()
                ->getCollection($relation->getForeignCollection())->getTableName();
            $this->addJoinRelation($relation, $tableName);

            return $tableName . '.' . Str::after($field, ':');
        }

        return $collection->getTableName() . '.' . $field;
    }
}
