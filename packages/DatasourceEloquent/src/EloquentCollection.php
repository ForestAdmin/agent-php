<?php

namespace ForestAdmin\AgentPHP\DatasourceEloquent;

use ForestAdmin\AgentPHP\BaseDatasource\BaseCollection;
use ForestAdmin\AgentPHP\BaseDatasource\Contracts\BaseDatasourceContract;
use ForestAdmin\AgentPHP\DatasourceEloquent\Utils\DataTypes;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use ReflectionClass;

class EloquentCollection extends BaseCollection
{
    /**
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function __construct(protected BaseDatasourceContract $datasource, protected Model $model)
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
        $inverse = $this->getInverseBelongsTo($relation);
        if ($inverse === HasOne::class) {
            $relationSchema = new OneToOneSchema(
                originKey: $relation->getForeignKeyName(),
                originKeyTarget:$relation->getOwnerKeyName(),
                foreignCollection: (new ReflectionClass($relation->getRelated()))->getShortName()
            );

            $this->addField($name, $relationSchema);
        } elseif ($inverse === HasMany::class) {
            $relationSchema = new ManyToOneSchema(
                foreignKey: $relation->getForeignKeyName(),
                foreignKeyTarget:$relation->getOwnerKeyName(),
                foreignCollection: (new ReflectionClass($relation->getRelated()))->getShortName()
            );

            $this->addField($name, $relationSchema);
        }
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

    private function getInverseBelongsTo(BelongsTo $relation): ?string
    {
        $relations = $this->getRelationships(new ReflectionClass($relation->getRelated()));

        return collect($relations)
            ->filter(fn ($class) => in_array($class, [HasMany::class, HasOne::class], true))
            ->first(fn ($class, $methodName) => class_basename($relation->getRelated()->$methodName()->getRelated()) === class_basename($this->model));
    }
}
