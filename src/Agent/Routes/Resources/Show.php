<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources;

use ForestAdmin\AgentPHP\Agent\Routes\AbstractCollectionRoute;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Utils\ContextFilterFactory;
use ForestAdmin\AgentPHP\Agent\Utils\Id;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;

class Show extends AbstractCollectionRoute
{
    public function setupRoutes(): AbstractRoute
    {
        $this->addRoute(
            'forest.show',
            'get',
            '/{collectionName}/{id}',
            fn ($args) => $this->handleRequest($args)
        );

        return $this;
    }

    public function handleRequest(array $args = []): array
    {
        $this->build($args);
        $this->permissions->can('read:' . $this->collection->getName());
        $id = Id::unpackId($this->collection, $args['id']);
        $filter = ContextFilterFactory::build(
            $this->collection,
            $this->request,
            ConditionTreeFactory::intersect(
                [
                    $this->permissions->getScope($this->collection),
                    ConditionTreeFactory::matchIds($this->collection, [$id]),
                ]
            )
        );

        return [
            'renderTransformer' => true,
            'name'              => $args['collectionName'],
            'content'           => $this->collection->show(
                $this->caller,
                $filter,
                $id,
                QueryStringParser::parseProjection($this->collection, $this->request)
            ),
        ];
    }
}
