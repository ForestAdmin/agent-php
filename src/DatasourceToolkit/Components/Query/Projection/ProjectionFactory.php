<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection;

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use function ForestAdmin\cache;

class ProjectionFactory
{
    public static function all(Collection $collection): Projection
    {
        $fields = $collection->getFields();
        $projectionFields = $fields->reduce(
            function ($memo, $column, $columnName) {
                if ($column->getType() === 'Column') {
                    return [...$memo, $columnName];
                }

                if ($column->getType() === 'OneToOne' || $column->getType() === 'ManyToOne') {
                    $relation = AgentFactory::get('datasource')->getCollection($column->getForeignCollection());
                    $relationFields = $relation->getFields();

                    return [
                        ...$memo,
                        ...$relationFields->keys()
                            ->filter(fn ($relationColumnName) => $relationFields->get($relationColumnName)->getType() === 'Column')
                            ->map(fn ($relationColumnName) => "$columnName:$relationColumnName")
                    ];
                }

                return $memo;
            },
            []
        );

        return new Projection($projectionFields);
    }
}
