<?php

namespace ForestAdmin\AgentPHP\DatasourceEloquent;

use ForestAdmin\AgentPHP\BaseDatasource\BaseCollection;
use ForestAdmin\AgentPHP\BaseDatasource\Contracts\BaseDatasourceContract;
use ForestAdmin\AgentPHP\DatasourceEloquent\Utils\DataTypes;
use ForestAdmin\AgentPHP\DatasourceEloquent\Utils\QueryAggregate;
use ForestAdmin\AgentPHP\DatasourceEloquent\Utils\QueryConverter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionClass;

class EloquentCollection extends BaseCollection
{
    /**
     * @throws \ReflectionException
     */
    public function __construct(protected BaseDatasourceContract $datasource, public Model $model)
    {
        $reflectionClass = new ReflectionClass($model);
        parent::__construct($datasource, $reflectionClass->getShortName(), $model->getTable());

        $this->addRelationships($reflectionClass);
    }

    /**
     * @throws \ReflectionException
     * @throws \Exception
     */
    private function addRelationships(ReflectionClass $reflectionClass): void
    {
        $relationships = $this->getRelationships($reflectionClass);

        foreach ($relationships as $name => $type) {
            $relation = $this->model->$name();
            match (get_class($relation)) {
                BelongsTo::class      => $this->addBelongsToRelation($name, $relation),
                BelongsToMany::class  => $this->addBelongsToManyRelation($name, $relation),
                HasMany::class        => $this->addHasManyRelation($name, $relation),
                HasOne::class         => $this->addHasOneRelation($name, $relation),
                default               => null
            };
        }
    }

    private function addBelongsToManyRelation(string $name, BelongsToMany $relation): void
    {
        $attributes = [
            'foreignKey'          => $relation->getRelatedPivotKeyName(),
            'foreignKeyTarget'    => $relation->getRelatedKeyName(),
            'originKey'           => $relation->getForeignPivotKeyName(),
            'originKeyTarget'     => $relation->getParentKeyName(),
            'foreignCollection'   => (new ReflectionClass($relation->getRelated()))->getShortName(),
        ];

        if ($model = $this->datasource->findModelByTableName($relation->getTable())) {
            $attributes['throughCollection'] = (new ReflectionClass($model))->getShortName();
        } else {
            $related = $relation->getRelated();
            $relatedName = (new ReflectionClass($related))->getShortName();
            $throughCollection = new ThroughCollection(
                $this->datasource,
                [
                    'name'               => Str::ucfirst(Str::camel($relation->getTable())),
                    'tableName'          => $relation->getTable(),
                    'relations'          => [
                        [
                            'foreignKey'        => $relation->getForeignPivotKeyName(),
                            'foreignKeyTarget'  => $relation->getParentKeyName(),
                            'foreignCollection' => $this->name,
                        ],
                        [
                            'foreignKey'        => $relation->getRelatedPivotKeyName(),
                            'foreignKeyTarget'  => $relation->getRelatedKeyName(),
                            'foreignCollection' => $relatedName,
                        ],
                    ],
                    'foreignCollections' => [
                        $this->tableName     => $this->name,
                        $related->getTable() => $relatedName,
                    ],
                ]
            );
            $this->datasource->addCollection($throughCollection);
            $attributes['throughCollection'] = $throughCollection->getName();
        }

        $relationSchema = new ManyToManySchema(...$attributes);
        $this->addField($name, $relationSchema);
    }

    private function addBelongsToRelation(string $name, BelongsTo $relation): void
    {
        $relationSchema = new ManyToOneSchema(
            foreignKey: $relation->getForeignKeyName(),
            foreignKeyTarget:$relation->getOwnerKeyName(),
            foreignCollection: (new ReflectionClass($relation->getRelated()))->getShortName()
        );

        $this->addField($name, $relationSchema);
    }

    private function addHasManyRelation(string $name, HasMany $relation): void
    {
        $relationSchema = new OneToManySchema(
            originKey: Str::after($relation->getForeignKeyName(), '.'),
            originKeyTarget: $relation->getLocalKeyName(),
            foreignCollection: (new ReflectionClass($relation->getRelated()))->getShortName()
        );
        $this->addField($name, $relationSchema);
    }

    private function addHasOneRelation(string $name, HasOne $relation): void
    {
        $relationSchema = new OneToOneSchema(
            originKey: $relation->getLocalKeyName(),
            originKeyTarget: Str::after($relation->getForeignKeyName(), '.'),
            foreignCollection: (new ReflectionClass($relation->getRelated()))->getShortName()
        );
        $this->addField($name, $relationSchema);
    }

    private function getRelationships($reflectionClass): array
    {
        return array_reduce(
            $reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC),
            function ($result, \ReflectionMethod $method) {
                ($returnType = $method->getReturnType()) &&
                empty($method->getParameters()) &&
                in_array($returnType->getName(), array_keys(DataTypes::$eloquentRelationships), true) &&
                ($result = array_merge($result, [$method->getName() => $returnType->getName()]));

                return $result;
            },
            []
        );
    }

    public function list(Caller $caller, Filter $filter, Projection $projection): array
    {
        return QueryConverter::of($this, $caller->getTimezone(), $filter, $projection)
            ->getQuery()
            ->get()
            ->map(fn ($record) => Arr::undot($record->toArray()))
            ->toArray();
    }

    public function create(Caller $caller, array $data)
    {
        $query = QueryConverter::of($this, $caller->getTimezone())->getQuery();
        $this->model::unguard();
        $record = $query->create($data)->toArray();
        $this->model::reguard();

        return $record;
    }

    public function update(Caller $caller, Filter $filter, array $patch): void
    {
        $this->model::unguard();
        QueryConverter::of($this, $caller->getTimezone(), $filter)->getQuery()->first()->fill($patch)->save();
        $this->model::reguard();
    }

    public function delete(Caller $caller, Filter $filter): void
    {
        QueryConverter::of($this, $caller->getTimezone(), $filter)
            ->getQuery()
            ->get()
            ->each(fn ($record) => $record->delete());
    }

    /**
     * @throws \Exception
     */
    public function aggregate(Caller $caller, Filter $filter, Aggregation $aggregation, ?int $limit = null)
    {
        return QueryAggregate::of($this, $caller->getTimezone(), $aggregation, $filter, $limit)->get();
    }
}
