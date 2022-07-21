<?php

namespace ForestAdmin\AgentPHP\Agent\Utils;

use Exception;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTree;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\ProjectionFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\ConditionTreeValidator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\ProjectionValidator;
use function ForestAdmin\config;

class QueryStringParser
{
    /**
     * @throws Exception
     */
    public static function parseConditionTree(Collection $collection, Request $request): ?ConditionTree
    {
        try {
            $filters = $request->input('data.attributes.all_records_subset_query.filters') ?? $request->input('filters');

            // check if return is a good idea
            if (! $filters) {
                return null;
            }

            if (is_string($filters)) {
                $filters = json_decode($filters, true, 512, JSON_THROW_ON_ERROR);
            }

            $conditionTree = ConditionTreeParser::fromPlainObject($collection, $filters);
            ConditionTreeValidator::validate($conditionTree, $collection);

            return $conditionTree;
        } catch (Exception $e) {
            throw new Exception('Invalid filters ' . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public static function parseProjection(Collection $collection, Request $request): Projection
    {
        try {
            $fields = $request->input('fields.' . $collection->getName());

            if ($fields === null) {
                return ProjectionFactory::all($collection);
            }
            $rootFields = collect(explode(',', $fields));
            $explicitRequest = $rootFields->map(
                static function ($field) use ($collection, $request) {
                    dump($field);
                    $column = $collection->getFields()->get($field);

                    return $column->getType() === 'Column' ?
                        $field : $field . ':' . $request->input("fields.$field");
                }
            );
            ProjectionValidator::validate($collection, $explicitRequest);

            return new Projection($explicitRequest->all());
        } catch (Exception $e) {
            throw new Exception('Invalid projection');
        }
    }

    public static function parseSearch(Collection $collection, Request $request): string
    {
        $search = $request->input('data.attributes.all_records_subset_query.search') ?? $request->get('search');

        if ($search && ! $collection->isSearchable()) {
            throw new ForestException('Collection is not searchable');
        }
    }

    public static function parseSearchExtended(Request $request): bool
    {
        $extended = $request->input('data.attributes.all_records_subset_query.searchExtended') ?? $request->get('searchExtended');

        return (bool)$extended;
    }

    public static function parseSegment(Collection $collection, Request $request): ?string
    {
        $segment = $request->input('data.attributes.all_records_subset_query.segment') ?? $request->get('segment');

        if (! $segment) {
            return null;
        }

        if (! $collection->getSegments()->contains($segment)) {
            throw new ForestException("Invalid segment: $segment");
        }

        return $segment;
    }
}
