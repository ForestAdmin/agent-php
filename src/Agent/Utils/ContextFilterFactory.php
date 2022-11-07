<?php

namespace ForestAdmin\AgentPHP\Agent\Utils;

use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTree;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;

class ContextFilterFactory
{
    public static function buildPaginated(CollectionContract $collection, Request $request, ?ConditionTree $scope): PaginatedFilter
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
    }

    public static function build(CollectionContract $collection, Request $request, ?ConditionTree $scope): Filter
    {
        return new Filter(
            conditionTree: ConditionTreeFactory::intersect(
                [
                    QueryStringParser::parseConditionTree($collection, $request),
                    $scope,
                ]
            ),
            search: QueryStringParser::parseSearch($collection, $request),
            searchExtended: QueryStringParser::parseSearchExtended($request),
            segment: QueryStringParser::parseSegment($collection, $request),
        );
    }
}
