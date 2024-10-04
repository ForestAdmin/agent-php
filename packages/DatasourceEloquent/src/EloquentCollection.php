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
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\PolymorphicManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\PolymorphicOneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\PolymorphicOneToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Collection as CollectionUtils;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionClass;

class EloquentCollection extends BaseCollection
{
    /**
     * @throws \ReflectionException
     */
    public function __construct(protected BaseDatasourceContract $datasource, public Model $model, protected $supportPolymorphicRelations = false)
    {
        $reflectionClass = new ReflectionClass($model);
        parent::__construct($datasource, CollectionUtils::fullNameToSnakeCase($reflectionClass->getName()), $model->getTable());

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
            if (in_array(get_class($relation->getRelated()), $this->datasource->getModels(), true)) {
                match (get_class($relation)) {
                    BelongsTo::class      => $this->addBelongsToRelation($name, $relation),
                    BelongsToMany::class  => $this->addBelongsToManyRelation($name, $relation),
                    HasMany::class        => $this->addHasManyRelation($name, $relation),
                    HasOne::class         => $this->addHasOneRelation($name, $relation),
                    default               => null
                };
                if ($this->supportPolymorphicRelations) {
                    match(get_class($relation)) {
                        MorphMany::class    => $this->addPolymorphicOneToManyRelation($name, $relation),
                        MorphOne::class     => $this->addPolymorphicOneToOneRelation($name, $relation),
                        MorphTo::class      => $this->addPolymorphicManyToOneRelation($name, $relation),
                        default             => null
                    };
                }
            }
        }
    }

    private function addBelongsToManyRelation(string $name, BelongsToMany $relation): void
    {
        $attributes = [
            'foreignKey'          => $relation->getRelatedPivotKeyName(),
            'foreignKeyTarget'    => $relation->getRelatedKeyName(),
            'originKey'           => $relation->getForeignPivotKeyName(),
            'originKeyTarget'     => $relation->getParentKeyName(),
            'foreignCollection'   => CollectionUtils::fullNameToSnakeCase((new ReflectionClass($relation->getRelated()))->getName()),
        ];

        if ($model = $this->datasource->findModelByTableName($relation->getTable())) {
            $attributes['throughCollection'] = CollectionUtils::fullNameToSnakeCase((new ReflectionClass($model))->getName());
        } else {
            $related = $relation->getRelated();
            $relatedName = CollectionUtils::fullNameToSnakeCase((new ReflectionClass($related))->getName());
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
            foreignKeyTarget: $relation->getOwnerKeyName(),
            foreignCollection: CollectionUtils::fullNameToSnakeCase((new ReflectionClass($relation->getRelated()))->getName())
        );

        $this->addField($name, $relationSchema);
    }

    private function addPolymorphicManyToOneRelation(string $name, MorphTo $relation): void
    {
        $foreignCollections = $this->getPolymorphicTypes();

        $relationSchema = new PolymorphicManyToOneSchema(
            foreignKeyTypeField: $relation->getMorphType(),
            foreignKey: $relation->getForeignKeyName(),
            foreignKeyTargets: $foreignCollections,
            foreignCollections: array_keys($foreignCollections)
        );

        $this->addField($name, $relationSchema);
    }

    private function addHasManyRelation(string $name, HasMany $relation): void
    {
        $relationSchema = new OneToManySchema(
            originKey: Str::after($relation->getForeignKeyName(), '.'),
            originKeyTarget: $relation->getLocalKeyName(),
            foreignCollection: CollectionUtils::fullNameToSnakeCase((new ReflectionClass($relation->getRelated()))->getName())
        );
        $this->addField($name, $relationSchema);
    }

    private function addPolymorphicOneToManyRelation(string $name, MorphMany $relation): void
    {
        $relationSchema = new PolymorphicOneToManySchema(
            originKey: Str::after($relation->getForeignKeyName(), '.'),
            originKeyTarget: $relation->getLocalKeyName(),
            foreignCollection: CollectionUtils::fullNameToSnakeCase((new ReflectionClass($relation->getRelated()))->getName()),
            originTypeField: $relation->getMorphType(),
            originTypeValue: $relation->getMorphClass()
        );

        $this->addField($name, $relationSchema);
    }

    private function addHasOneRelation(string $name, HasOne $relation): void
    {
        $relationSchema = new OneToOneSchema(
            originKey: Str::after($relation->getForeignKeyName(), '.'),
            originKeyTarget: $relation->getLocalKeyName(),
            foreignCollection: CollectionUtils::fullNameToSnakeCase((new ReflectionClass($relation->getRelated()))->getName())
        );
        $this->addField($name, $relationSchema);
    }

    private function addPolymorphicOneToOneRelation(string $name, MorphOne $relation): void
    {
        $relationSchema = new PolymorphicOneToOneSchema(
            originKey: Str::after($relation->getForeignKeyName(), '.'),
            originKeyTarget: $relation->getLocalKeyName(),
            foreignCollection: CollectionUtils::fullNameToSnakeCase((new ReflectionClass($relation->getRelated()))->getName()),
            originTypeField: $relation->getMorphType(),
            originTypeValue: $relation->getMorphClass()
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
            ->map(function ($record) {
                $record = collect($record->getAttributes())
                    ->reject(fn ($value, $key) => Str::startsWith($key, 'polymorphic_') && $value === null)
                    ->mapWithKeys(function ($value, $key) {
                        if (Str::startsWith($key, 'polymorphic_')) {
                            $key = Str::replace('polymorphic_', '', $key);
                            $relationName = Str::before(Str::before($key, '.'), '_');

                            return [$relationName . '.' . Str::after($key, '.') => $value];
                        }

                        return [$key => $value];
                    });

                return Arr::undot($record->toArray());
            })
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
            ->each(function ($record) {
                $model = new ReflectionClass($record);
                $relations = $this->getRelationships($model);
                foreach ($relations as $name => $type) {
                    if ($type === 'Illuminate\Database\Eloquent\Relations\MorphOne') {
                        $this->nullablePolymorphicMorphOneRelationFields($record, $name);
                    }
                    if ($type === 'Illuminate\Database\Eloquent\Relations\MorphMany') {
                        $this->nullablePolymorphicMorphManyRelationFields($record, $name);
                    }
                }
                $record->delete();
            });
    }

    /**
     * @throws \Exception
     */
    public function aggregate(Caller $caller, Filter $filter, Aggregation $aggregation, ?int $limit = null)
    {
        return QueryAggregate::of($this, $caller->getTimezone(), $aggregation, $filter, $limit)->get();
    }

    /**
     * @throws \ReflectionException
     */
    private function getPolymorphicTypes(): array
    {
        $types = [];

        foreach ($this->datasource->getModels() as $model) {
            $reflectionClass = new ReflectionClass($model);
            $model = new $model();

            $hasPolymorphicType = collect($this->getRelationships($reflectionClass))
                ->filter(fn ($class) => in_array($class, [MorphOne::class, MorphMany::class], true))
                ->first(fn ($class, $methodName) => class_basename($model->$methodName()->getRelated()) === class_basename($this->model));

            if (! empty($hasPolymorphicType)) {
                $types[$reflectionClass->getName()] = $model->getKeyName();
            }
        }

        return $types;
    }

    public function getModel(): Model
    {
        return $this->model;
    }

    private function nullablePolymorphicMorphOneRelationFields($record, string $relationName): void
    {
        try {
            $relation = $record->$relationName();
            $foreignKey = $relation->getForeignKeyName();
            $morphType = $relation->getMorphType();

            if ($record->$relationName !== null) {
                $record->$relationName->fill([
                    $foreignKey => null,
                    $morphType  => null,
                ])->save();
            }
        } catch (\Exception $e) {
            // Do nothing
        }
    }

    private function nullablePolymorphicMorphManyRelationFields($record, $relationName): void
    {
        $relation = $record->$relationName();
        $foreignKey = $relation->getForeignKeyName();
        $morphType = $relation->getMorphType();

        try {
            $record->$relationName->each(function ($r) use ($foreignKey, $morphType) {
                $r->fill([
                    $foreignKey => null,
                    $morphType  => null,
                ])->save();
            });
        } catch (\Exception $e) {
            // Do nothing
        }
    }
}
