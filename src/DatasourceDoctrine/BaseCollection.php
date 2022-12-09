<?php

namespace ForestAdmin\AgentPHP\DatasourceDoctrine;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use ForestAdmin\AgentPHP\Agent\Utils\QueryCharts;
use ForestAdmin\AgentPHP\Agent\Utils\QueryConverter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection as ForestCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use Illuminate\Support\Arr;
use Symfony\Component\PropertyAccess\PropertyAccess;

class BaseCollection extends ForestCollection
{
    protected string $tableName;

    /**
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function __construct(protected DoctrineDatasource $datasource, string $name)
    {
        parent::__construct($datasource, $name);
    }

    public function show(Caller $caller, Filter $filter, $id, Projection $projection)
    {
        return Arr::undot(QueryConverter::of($this, $caller->getTimezone(), $filter, $projection)->first());
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
        $data = $this->formatAttributes($data);
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
    public function update(Caller $caller, Filter $filter, array $patch)
    {
        $data = $this->formatAttributes($patch);
        QueryConverter::of($this, $caller->getTimezone(), $filter)->update($data);

        return Arr::dot(QueryConverter::of($this, $caller->getTimezone(), $filter)->first() ?? []);
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

    public function formatAttributes(array $data)
    {
        $entityAttributes = [];
        $attributes = $data['attributes'];
        $relationships = $data['relationships'] ?? [];
        foreach ($attributes as $key => $value) {
            $entityAttributes[$key] = $value;
        }

        foreach ($relationships as $key => $value) {
            $relation = $this->getFields()[$key];
            $attributes = $value['data'];
            if ($relation instanceof ManyToOneSchema) {
                $entityAttributes[$relation->getForeignKey()] = $attributes[$relation->getForeignKeyTarget()];
            }
        }

        return $entityAttributes;
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
