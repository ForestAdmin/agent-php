<?php

namespace ForestAdmin\AgentPHP\Agent\Utils;

use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTree;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Page;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Sort;

class ContextFilterFactory
{
    /*
     * static buildPaginated(
        collection: Collection,
        context: Context,
        scope: ConditionTree,
        partialFilter?: Partial<PaginatedFilter>,
      ): PaginatedFilter {
        return new PaginatedFilter({
          sort: QueryStringParser.parseSort(collection, context),
          page: QueryStringParser.parsePagination(context),
          ...ContextFilterFactory.build(collection, context, scope),
          ...partialFilter,
        });
      }

      static build(
        collection: Collection,
        context: Context,
        scope: ConditionTree,
        partialFilter?: Partial<Filter>,
      ): PaginatedFilter {
        return new Filter({
          search: QueryStringParser.parseSearch(collection, context),
          segment: QueryStringParser.parseSegment(collection, context),
          searchExtended: QueryStringParser.parseSearchExtended(context),
          conditionTree: ConditionTreeFactory.intersect(
            QueryStringParser.parseConditionTree(collection, context),
            scope,
          ),
          ...partialFilter,
        });
      }
     */

    public static function buildPaginated(Collection $collection, Request $request, ConditionTree $scope, array $paginatedFilters)
    {
        return new PaginatedFilter(
            self::build($collection, $request, $scope, $paginatedFilters),
            sort: new Sort(),
            page: new Page(),
        );
    }

    public static function build(Collection $collection, Request $request, ConditionTree $scope, array $paginatedFilters)
    {
        return new Filter(
            conditionTree: new ConditionTreeLeaf(), // TODO NEED TO FIX TO ConditionTree
            search: null,
            searchExtended: null,
            segment: null,
        );
    }
}
