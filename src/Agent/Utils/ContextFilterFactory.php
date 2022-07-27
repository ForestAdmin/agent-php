<?php

namespace ForestAdmin\AgentPHP\Agent\Utils;

use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTree;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;

class ContextFilterFactory
{
    public static function buildPaginated(Collection $collection, Request $request, ?ConditionTree $scope, array $paginatedFilters = [])
    {
        return new PaginatedFilter(
            conditionTree: ConditionTreeFactory::intersect(
                [
                    QueryStringParser::parseConditionTree($collection, $request),
                    $scope,
                ]
            ),
            search: QueryStringParser::parseSearch($collection, $request),
            searchExtended: QueryStringParser::parseSearchExtended($request),
            segment: QueryStringParser::parseSegment($collection, $request),
            sort: QueryStringParser::parseSort($collection, $request),
            page: QueryStringParser::parsePagination($request)
        );
        // todo merge $paginatedFilters
    }

    public static function build(Collection $collection, Request $request, ?ConditionTree $scope, array $paginatedFilters = [])
    {
        return new Filter(
            conditionTree: null, // TODO NEED TO FIX TO ConditionTree
            search: null,
            searchExtended: null,
            segment: null,
        );
    }
}
