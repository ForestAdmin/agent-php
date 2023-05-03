<?php

namespace ForestAdmin\AgentPHP\BaseDatasource;

use ForestAdmin\AgentPHP\Agent\Utils\QueryAggregate;
use ForestAdmin\AgentPHP\Agent\Utils\QueryConverter;
use ForestAdmin\AgentPHP\BaseDatasource\Contracts\BaseDatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection as ForestCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
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
            ->getQuery()
            ->get()
            ->map(fn ($record) => Arr::undot($record))
            ->toArray();
    }

    public function create(Caller $caller, array $data)
    {
        $query = QueryConverter::of($this, $caller->getTimezone())->getQuery();
        $id = $query->insertGetId($data);

        $filter = new Filter(
            conditionTree: ConditionTreeFactory::matchIds($this, [$id])
        );

        return Arr::dot(QueryConverter::of($this, $caller->getTimezone(), $filter)->first());
    }

    public function update(Caller $caller, Filter $filter, array $patch): void
    {
        QueryConverter::of($this, $caller->getTimezone(), $filter)->getQuery()->update($patch);
    }

    public function delete(Caller $caller, Filter $filter): void
    {
        QueryConverter::of($this, $caller->getTimezone(), $filter)->getQuery()->delete();
    }

    /**
     * @throws \Exception
     */
    public function aggregate(Caller $caller, Filter $filter, Aggregation $aggregation, ?int $limit = null)
    {
        return QueryAggregate::of($this, $caller->getTimezone(), $aggregation, $filter, $limit)->get();
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

    public function renderChart(Caller $caller, string $name, array $recordId)
    {
        throw new ForestException("Chart $name is not implemented.");
    }
}
