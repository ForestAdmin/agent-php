<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources;

use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;

class Destroy extends CollectionRoute
{
    public function setupRoutes(): AbstractRoute
    {
        $this->addRoute(
            'forest.destroy',
            'delete',
            '/{collectionName}/{id}',
            fn ($args) => $this->handleRequest($args)
        );

        $this->addRoute(
            'forest.destroy_bulk',
            'delete',
            '/{collectionName}',
            fn ($args) => $this->handleRequestBulk($args)
        );

        return $this;
    }

    public function handleRequest(array $args = []): array
    {
        $this->build($args);

        $this->permissions->can('delete:' . $this->collection->getName(), $this->collection->getName());
        $this->collection->delete($this->paginatedFilter, $args['id']);

        return [
            'content' => null,
            'status'  => 204,
        ];
    }

    public function handleRequestBulk(array $args = []): array
    {
        $this->build($args);
        $attributes = $this->request->get('data')['attributes'];
        $ids = $attributes['ids'];
        $allRecords = $attributes['all_records'];
        $idsExcluded = $attributes['all_records_ids_excluded'];
        $a = QueryStringParser::parseSort($this->collection, $this->request);
        dd($a);

        $this->collection->deleteBulk($this->filter, $ids, $allRecords, $idsExcluded);

        return [
            'content' => null,
            'status'  => 204,
        ];
    }
}
