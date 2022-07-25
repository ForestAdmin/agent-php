<?php

namespace ForestAdmin\AgentPHP\Agent\Utils;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTree;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Page;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\ProjectionFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Sort;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Sort\SortFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\ConditionTreeValidator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\ProjectionValidator;

use function ForestAdmin\config;

class QueryStringParser
{
    public const DEFAULT_ITEMS_PER_PAGE = 15;

    public const DEFAULT_PAGE_TO_SKIP = 1;

    /**
     * @throws ExForestExceptionception
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
            throw new ForestException('Invalid filters ' . $e->getMessage());
        }
    }

    /**
     * @throws ForestException
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
        } catch (\Exception $e) {
            throw new ForestException('Invalid projection');
        }
    }

    /**
     * @throws ForestException
     */
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

        return (bool) $extended;
    }

    /**
     * @throws ForestException
     */
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

    /**
     * @throws ForestException
     */
    public static function parseCaller(Request $request): Caller
    {
        $timezone = $request->get('timezone');

        if (! $timezone) {
            throw new ForestException('Missing timezone');
        }

        if (! in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
            throw new ForestException("Invalid timezone: $timezone");
        }

        $tokenData = JWT::decode($request->bearerToken(), new Key(config('envSecret'), 'HS256'));

        return Caller::makeFromRequestData($tokenData, $timezone);
    }

    /**
     * @throws ForestException
     */
    public static function parsePagination(Request $request): Page
    {
        $queryItemsPerPage = $request->input('data.attributes.all_records_subset_query')['size'] ??
            $request->get('page')['size'] ??
            self::DEFAULT_ITEMS_PER_PAGE;

        $queryPageToSkip = $request->input('data.attributes.all_records_subset_query')['number'] ??
            $request->get('page')['number'] ??
            self::DEFAULT_PAGE_TO_SKIP;

        if (! is_numeric($queryItemsPerPage) ||
            ! is_numeric($queryPageToSkip) ||
            $queryItemsPerPage <= 0 ||
            $queryPageToSkip <= 0) {
            throw new ForestException("Invalid pagination [limit: $queryItemsPerPage, skip: $queryPageToSkip]");
        }

        $offset = ($queryPageToSkip - 1) * $queryItemsPerPage;

        return new Page($offset, $queryItemsPerPage);
    }

    public static function parseSort(Collection $collection, Request $request): Sort
    {
        $sortString = $request->input('data.attributes.all_records_subset_query.sort') ?? $request->get('sort');

        if (! $sortString) {
            return SortFactory::byPrimaryKeys($collection);
        }

        if ($sortString[0] === '-') {
            return new Sort(substr($sortString, 1), false);
        } else {
            return new Sort($sortString);
        }
    }
}
