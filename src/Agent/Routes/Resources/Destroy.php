<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources;

use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;

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

        $this->collection->deleteBulk($this->paginatedFilter, $ids, $allRecords, $idsExcluded);

        return [
            'content' => null,
            'status'  => 204,
        ];
    }
}
