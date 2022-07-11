<?php

namespace ForestAdmin\AgentPHP\Agent\Utils;

use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTree;

class QueryStringParser
{


    /**
     * @throws \Exception
     */
    public static function parseConditionTree(Collection $collection, Request $request): ?ConditionTree
    {
        try {
            $filters = $request->input('data.attributes.all_records_subset_query.filters') ?? $request->input('filters');

            if (! $filters) {
                return null;
            }
            if (is_string($filters)) {
                $filters = json_decode($filters, true, 512, JSON_THROW_ON_ERROR);
            }

            $conditionTree = ConditionTreeParser::fromPlainObject($collection, $filters);
            //ConditionTreeValidator.validate(conditionTree, collection); TODO

            return $conditionTree;
        } catch (\Exception $e) {
            throw new \Exception('Invalid filters ' . $e->getMessage());
        }
    }
}
