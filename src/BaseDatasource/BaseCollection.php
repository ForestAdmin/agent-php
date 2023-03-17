<?php

namespace ForestAdmin\AgentPHP\BaseDatasource;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use ForestAdmin\AgentPHP\Agent\Utils\QueryCharts;
use ForestAdmin\AgentPHP\Agent\Utils\QueryConverter;
use ForestAdmin\AgentPHP\BaseDatasource\Contracts\BaseDatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection as ForestCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use Illuminate\Support\Arr;

class BaseCollection extends ForestCollection
{
    protected string $tableName;

    public function __construct(protected BaseDatasourceContract $datasource, string $name, string $tableName)
    {
        $this->tableName = $tableName;
        parent::__construct($datasource, $name);
    }

    public function list(Caller $caller, Filter $filter, Projection $projection): array
    {
        return QueryConverter::of($this, $caller->getTimezone(), $filter, $projection)
            ->get()
            ->map(fn ($record) => Arr::undot($record))
            ->toArray();
    }

    public function create(Caller $caller, array $data)
    {
        $query = QueryConverter::of($this, $caller->getTimezone());
        $id = $query->insertGetId($data);

        $filter = new Filter(
            conditionTree: ConditionTreeFactory::matchIds($this, [$id])
        );

        return Arr::dot(QueryConverter::of($this, $caller->getTimezone(), $filter)->first());
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function update(Caller $caller, Filter $filter, array $patch): void
    {
        QueryConverter::of($this, $caller->getTimezone(), $filter)->update($patch);
    }

    public function delete(Caller $caller, Filter $filter): void
    {
        QueryConverter::of($this, $caller->getTimezone(), $filter)->delete();
    }

    /**
     * @throws \Exception
     */
    public function aggregate(Caller $caller, Filter $filter, Aggregation $aggregation, ?int $limit = null, ?string $chartType = null)
    {
        $query = QueryConverter::of($this, $caller->getTimezone(), $filter, $aggregation->getProjection());

        if ($chartType) {
            return QueryCharts::of($this, $query, $aggregation, $limit)
                ->{'query' . $chartType}()
                ->map(fn ($result) => is_array($result) || is_object($result) ? Arr::undot($result) : $result)
                ->toArray();
        } else {
            return $query->count();
        }
    }

    public function toArray($record, ?Projection $projection = null): array
    {
        return $record;
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }
}
