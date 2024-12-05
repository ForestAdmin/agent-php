<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources;

use ForestAdmin\AgentPHP\Agent\Facades\JsonApi;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractCollectionRoute;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Utils\Csv;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\Agent\Utils\Traits\QueryHandler;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use Illuminate\Support\Str;

class Listing extends AbstractCollectionRoute
{
    use QueryHandler;

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
        $this->permissions->can('browse', $this->collection);

        $this->filter = new PaginatedFilter(
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
            sort: QueryStringParser::parseSort($this->collection, $this->request),
            page: QueryStringParser::parsePagination($this->request)
        );

        $results = $this->collection->list(
            $this->caller,
            $this->filter,
            QueryStringParser::parseProjectionWithPks($this->collection, $this->request)
        );

        return [
            'name'    => $args['collectionName'],
            'content' => JsonApi::renderCollection($results, $this->collection->makeTransformer(), $args['collectionName'], $this->request),
        ];
    }

    public function handleRequestCsv(array $args = []): array
    {
        $this->build($args);
        $this->permissions->can('browse', $this->collection);
        $this->permissions->can('export', $this->collection);

        $this->filter = new PaginatedFilter(
            conditionTree: ConditionTreeFactory::intersect(
                [
                    $this->permissions->getScope($this->collection),
                    QueryStringParser::parseConditionTree($this->collection, $this->request),
                    $this->parseQuerySegment($this->collection, $this->permissions, $this->caller),
                ]
            ),
            search: QueryStringParser::parseSearch($this->collection, $this->request),
            searchExtended: QueryStringParser::parseSearchExtended($this->request),
        );

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
