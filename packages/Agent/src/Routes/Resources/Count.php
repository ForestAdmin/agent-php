<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources;

use ForestAdmin\AgentPHP\Agent\Routes\AbstractCollectionRoute;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\Agent\Utils\Traits\QueryHandler;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;

class Count extends AbstractCollectionRoute
{
    use QueryHandler;

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
            $this->filter = new Filter(
                conditionTree: ConditionTreeFactory::intersect(
                    [
                        $this->permissions->getScope($this->collection),
                        QueryStringParser::parseConditionTree($this->collection, $this->request),
                        $this->parseQuerySegment($this->collection, $this->permissions, $this->caller),
                    ]
                ),
                search: QueryStringParser::parseSearch($this->collection, $this->request),
                searchExtended: QueryStringParser::parseSearchExtended($this->request),
                segment: QueryStringParser::parseSegment($this->collection, $this->request),
            );
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
