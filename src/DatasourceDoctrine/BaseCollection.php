<?php

namespace ForestAdmin\AgentPHP\DatasourceDoctrine;

use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\Mapping\MappingException;
use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\FrontendFilterable;
use ForestAdmin\AgentPHP\Agent\Utils\QueryCharts;
use ForestAdmin\AgentPHP\Agent\Utils\QueryConverter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection as ForestCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
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

    public function export(Caller $caller, Filter $filter, Projection $projection): array
    {
        $results = QueryConverter::of($this, $caller->getTimezone(), $filter, $projection)
            ->get()
            ->map(fn ($record) => Arr::undot($record))
            ->toArray();

        $relations = $projection->relations()->keys()->toArray();
        foreach ($results as &$result) {
            foreach ($result as $field => $value) {
                if (is_array($value) && in_array($field, $relations, true)) {
                    $result[$field] = array_shift($value);
                }
            }
        }

        return $results;
    }

    public function create(Caller $caller, array $data)
    {
        $data = $this->formatAttributes($data);
        $query = QueryConverter::of($this, $caller->getTimezone());
        $id = $query->insertGetId($data);

        $filter = new Filter(
            conditionTree: new ConditionTreeLeaf($this->getIdentifier(), Operators::EQUAL, $id)
        );

        return Arr::dot(QueryConverter::of($this, $caller->getTimezone(), $filter)->first());
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function update(Caller $caller, Filter $filter, $id, array $patch)
    {
        $data = $this->formatAttributes($patch);
        QueryConverter::of($this, $caller->getTimezone(), $filter)->update($data);

        return Arr::dot(QueryConverter::of($this, $caller->getTimezone(), $filter)->first());
    }

    public function delete(Caller $caller, Filter $filter, $id): void
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

    public function associate(Caller $caller, Filter $parentFilter, Filter $childFilter, OneToManySchema|ManyToManySchema $relation): void
    {
        $entity = Arr::dot(QueryConverter::of($this, $caller->getTimezone(), $parentFilter)->first());
        $targetCollection = $this->datasource->getCollection($relation->getForeignCollection());

        if ($relation instanceof ManyToManySchema) {
            $entitiesTarget = QueryConverter::of($targetCollection, $caller->getTimezone(), $childFilter)
                ->get()
                ->map(fn ($record) => Arr::undot($record))
                ->toArray();

            /** @var Builder $query */
            $query = $this->getDataSource()
                ->getOrm()
                ->getConnection()
                ->table($relation->getThroughTable());
            foreach ($entitiesTarget as $entityTarget) {
                $query->updateOrInsert(
                    [
                        $relation->getForeignKey() => $entityTarget[$relation->getForeignKeyTarget()],
                        $relation->getOriginKey()  => $entity[$relation->getOriginKeyTarget()],
                    ],
                    []
                );
            }
        } else {
            QueryConverter::of($targetCollection, $caller->getTimezone(), $childFilter)->update(
                [
                    $relation->getOriginKey() => $entity[$this->getIdentifier()],
                ]
            );
        }
    }

    public function dissociate(Caller $caller, Filter $parentFilter, Filter $childFilter, OneToManySchema|ManyToManySchema $relation): void
    {
        $entity = Arr::dot(QueryConverter::of($this, $caller->getTimezone(), $parentFilter)->first());
        $targetCollection = $this->datasource->getCollection($relation->getForeignCollection());

        if ($relation instanceof ManyToManySchema) {
            $entitiesTarget = QueryConverter::of($targetCollection, $caller->getTimezone(), $childFilter)
                ->get()
                ->map(fn ($record) => Arr::undot($record))
                ->toArray();

            foreach ($entitiesTarget as $entityTarget) {
                $this->getDataSource()
                    ->getOrm()
                    ->getConnection()
                    ->table($relation->getThroughTable())
                    ->where(
                        [
                            [$relation->getForeignKey(), '=', $entityTarget[$relation->getForeignKeyTarget()]],
                            [$relation->getOriginKey(), '=', $entity[$relation->getOriginKeyTarget()]],
                        ]
                    )->delete();
            }
        } else {
            // todo
        }
    }

    protected function formatAttributes(array $data)
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
