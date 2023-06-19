<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources\Related;

use ForestAdmin\AgentPHP\Agent\Routes\AbstractRelationRoute;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Utils\ContextFilterFactory;
use ForestAdmin\AgentPHP\Agent\Utils\Id;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Collection as CollectionUtils;

class CountRelated extends AbstractRelationRoute
{
    public function setupRoutes(): AbstractRoute
    {
        $this->addRoute(
            'forest.related.count',
            'get',
            '/{collectionName}/{id}/relationships/{relationName}/count',
            fn ($args) => $this->handleRequest($args)
        );

        return $this;
    }

    public function handleRequest(array $args = []): array
    {
        $this->build($args);
        $this->permissions->can('browse', $this->collection);
        $scope = $this->permissions->getScope($this->childCollection);

        if ($this->childCollection->isCountable()) {
            $this->filter = ContextFilterFactory::build($this->childCollection, $this->request, $scope);

            $id = Id::unpackId($this->collection, $args['id']);

            $count = CollectionUtils::aggregateRelation(
                $this->collection,
                $id,
                $args['relationName'],
                $this->caller,
                $this->filter,
                new Aggregation(operation: 'Count')
            )[0]['value'] ?? 0;

            return [
                'content' => compact('count'),
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
