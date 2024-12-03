<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources;

use ForestAdmin\AgentPHP\Agent\Routes\AbstractCollectionRoute;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;

class Count extends AbstractCollectionRoute
{
    public function setupRoutes(): AbstractRoute
    {
        $this->addRoute(
            'forest.count',
            'get',
            '/{collectionName}/count',
            fn ($args) => $this->handleRequest($args)
        );

        return $this;
    }

    public function handleRequest(array $args = []): array
    {
        $this->build($args);
        $this->permissions->can('browse', $this->collection);

        if ($this->collection->isCountable()) {
            $this->filter = new Filter(conditionTree: $this->permissions->getScope($this->collection));
            $aggregation = new Aggregation(operation: 'Count');

            return [
                'content' => [
                    'count' => $this->collection->aggregate($this->caller, $this->filter, $aggregation)[0]['value'] ?? 0,
                ],
            ];
        } else {
            return [
                'content' => [
                    'meta' => [
                        'count' => 'deactivated',
                    ],
                ],
            ];
        }
    }
}
