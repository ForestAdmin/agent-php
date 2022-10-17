<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources\Related;

use ForestAdmin\AgentPHP\Agent\Facades\JsonApi;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRelationRoute;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Utils\ContextFilterFactory;
use ForestAdmin\AgentPHP\Agent\Utils\Csv;
use ForestAdmin\AgentPHP\Agent\Utils\Id;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Collection as CollectionUtils;
use Illuminate\Support\Str;

class ListingRelated extends AbstractRelationRoute
{
    public function setupRoutes(): AbstractRoute
    {
        $this->addRoute(
            'forest.related.list',
            'get',
            '/{collectionName}/{id}/relationships/{relationName}',
            fn ($args) => $this->handleRequest($args)
        );

        return $this;
    }

    public function handleRequest(array $args = []): array
    {
        if (Str::endsWith($args['relationName'], '.csv')) {
            $args['relationName'] = Str::replaceLast('.csv', '', $args['relationName']);

            return $this->handleRequestCsv($args);
        }

        $this->build($args);
        $this->permissions->can('browse:' . $this->childCollection->getName());
        $scope = $this->permissions->getScope($this->childCollection);
        $filter = ContextFilterFactory::buildPaginated($this->childCollection, $this->request, $scope);

        $id = Id::unpackId($this->collection, $args['id']);

        $results = CollectionUtils::listRelation(
            $this->collection,
            $id,
            $args['relationName'],
            $this->caller,
            $filter,
            QueryStringParser::parseProjectionWithPks($this->childCollection, $this->request)
        );

        return [
            'name'              => $this->childCollection->getName(),
            'content'           => JsonApi::renderCollection($results, $this->collection->makeTransformer(), $this->childCollection->getName()),
        ];
    }

    public function handleRequestCsv(array $args = []): array
    {
        $this->build($args);
        $this->permissions->can('browse:' . $this->childCollection->getName());
        $this->permissions->can('export:' . $this->childCollection->getName());

        $scope = $this->permissions->getScope($this->childCollection);
        $filter = ContextFilterFactory::build($this->collection, $this->request, $scope);

        $id = Id::unpackId($this->collection, $args['id']);

        $rows = CollectionUtils::listRelation(
            $this->collection,
            $id,
            $args['relationName'],
            $this->caller,
            $filter,
            QueryStringParser::parseProjectionWithPks($this->childCollection, $this->request),
            'export'
        );

        $filename = $this->request->input('filename', $this->childCollection->getName()) . '.csv';
        $header = explode(',', $this->request->get('header'));

        return [
            'content' => Csv::make($rows, $header),
            'headers' => [
                'Content-type'        => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ],
        ];
    }
}
