<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources;

use ForestAdmin\AgentPHP\Agent\Facades\JsonApi;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractCollectionRoute;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Utils\ContextFilterFactory;
use ForestAdmin\AgentPHP\Agent\Utils\Csv;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use Illuminate\Support\Str;

class Listing extends AbstractCollectionRoute
{
    public function setupRoutes(): AbstractRoute
    {
        $this->addRoute(
            'forest.list',
            'get',
            '/{collectionName}',
            fn ($args) => $this->handleRequest($args)
        );

        return $this;
    }

    public function handleRequest(array $args = []): array
    {
        if (Str::endsWith($args['collectionName'], '.csv')) {
            $args['collectionName'] = Str::replaceLast('.csv', '', $args['collectionName']);

            return $this->handleRequestCsv($args);
        }

        $this->build($args);
        $this->permissions->can('browse:' . $this->collection->getName());
        $scope = $this->permissions->getScope($this->collection);
        $this->filter = ContextFilterFactory::buildPaginated($this->collection, $this->request, $scope);

        $results = $this->collection->list(
            $this->caller,
            $this->filter,
            QueryStringParser::parseProjectionWithPks($this->collection, $this->request)
        );

        return [
            'name'    => $args['collectionName'],
            'content' => JsonApi::renderCollection($results, $this->collection->makeTransformer(), $args['collectionName']),
        ];
    }

    public function handleRequestCsv(array $args = []): array
    {
        $this->build($args);
        $this->permissions->can('browse:' . $this->collection->getName());
        $this->permissions->can('export:' . $this->collection->getName());

        $scope = $this->permissions->getScope($this->collection);
        $this->filter = ContextFilterFactory::buildPaginated($this->collection, $this->request, $scope);
        $projection = QueryStringParser::parseProjection($this->collection, $this->request);
        $rows = $this->collection->list(
            $this->caller,
            $this->filter,
            $projection
        );

        $relations = $projection->relations()->keys()->toArray();
        foreach ($rows as &$row) {
            foreach ($row as $field => $value) {
                if (is_array($value) && in_array($field, $relations, true)) {
                    $row[$field] = array_shift($value);
                }
            }
        }

        $filename = $this->request->input('filename', $this->collection->getName()) . '.csv';
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
