<?php

namespace ForestAdmin\AgentPHP\DatasourceEloquent;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\FrontendFilterable;
use ForestAdmin\AgentPHP\BaseDatasource\BaseCollection;
use ForestAdmin\AgentPHP\BaseDatasource\Contracts\BaseDatasourceContract;
use ForestAdmin\AgentPHP\DatasourceEloquent\Utils\DataTypes;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
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
     * @throws \Exception
     */
    public function __construct(protected BaseDatasourceContract $datasource, protected Model $model)
    {
        $reflectionClass = new ReflectionClass($model);
        parent::__construct($datasource, $reflectionClass->getShortName(), $model->getTable());

        $this->addFieldsFromTable();
        $this->addRelationships($reflectionClass);
    }

    private function addFieldsFromTable(): void
    {
        /** @var Table $rawFields */
        $table = $this->datasource->getOrm()->getDatabaseManager()->getDoctrineSchemaManager()->introspectTable($this->tableName);
        $primaries = [];

        foreach ($table->getIndexes() as $index) {
            if ($index->isPrimary()) {
                $primaries[] = $index->getColumns();
            }
        }

        $fields = [
            'columns'   => $table->getColumns(),
            'primaries' => Arr::flatten($primaries),
        ];

        $this->addFields($fields);
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
            switch (get_class($relation)) {
                case BelongsTo::class:
                    //                    if ($name == 'user' || $name == 'post') {
                    //                        dump($this->model, $relation);
                    //                    }
                    $relationSchema = new OneToOneSchema(
                        originKey: $relation->getForeignKeyName(),
                        originKeyTarget:$relation->getOwnerKeyName(),
                        foreignCollection: (new ReflectionClass($relation->getRelated()))->getShortName()
                    );
                    $this->addField($name, $relationSchema);

                    break;
                case BelongsToMany::class:
                    $attributes = [
                        'foreignKey'          => $relation->getRelatedPivotKeyName(),
                        'foreignKeyTarget'    => $relation->getRelatedKeyName(),
                        'originKey'           => $relation->getForeignPivotKeyName(),
                        'originKeyTarget'     => $relation->getParentKeyName(),
                        'foreignCollection'   => (new ReflectionClass($relation->getRelated()))->getShortName(),
                    ];

                    if ($model = $this->datasource->findModelByTableName($relation->getTable())) {
                        $attributes['throughCollection'] = (new ReflectionClass($relation->getRelated()))->getShortName();
                    } else {
                        // TODO
                        dd('dfsf');
                        //                        $this->datasource->addCollection(
                        //                            new ThroughCollection(
                        //                                $this->datasource,
                        //                                [
                        //                                    'name'               => Str::camel($relation->getTable()),
                        //                                    'columns'            => [], // $relation->getPivotColumns()?
                        //                                    'foreignKeys'        => $schemaTable->getForeignKeys(),
                        //                                    'primaryKey'         => $schemaTable->getPrimaryKey(),
                        //                                    'foreignCollections' => [
                        //                                        $this->tableName                        => $this->name,
                        //                                        $relatedMeta->getTableName()            => $relatedMeta->reflClass->getShortName(),
                        //                                    ],
                        //                                ]
                        //                            )
                        //                        );
                        $attributes['throughCollection'] = Str::camel($relation->getTable());
                    }

                    $relationSchema = new ManyToManySchema(...$attributes);
                    $this->addField($name, $relationSchema);

                    break;
                case HasMany::class:
                    $relationSchema = new OneToManySchema(
                        originKey: Str::after($relation->getForeignKeyName(), '.'),
                        originKeyTarget: $relation->getLocalKeyName(),
                        foreignCollection: (new ReflectionClass($relation->getRelated()))->getShortName()
                    );
                    $this->addField($name, $relationSchema);

                    break;
                case HasOne::class:
                    $relationSchema = new OneToOneSchema(
                        originKey: Str::after($relation->getForeignKeyName(), '.'),
                        originKeyTarget:$relation->getLocalKeyName(),
                        foreignCollection: (new ReflectionClass($relation->getRelated()))->getShortName()
                    );
                    $this->addField($name, $relationSchema);

                    break;
            }
        }
        //        foreach ($relationships as $key => $value) {
        //            $type = $this->getRelationType($this->entityMetadata->reflFields[$key]->getAttributes());
        //            if ($type) {
        //                match ($type) {
        //                    'ManyToMany' => $this->addManyToMany($key, $value['joinTable'], $value['targetEntity'], $value['mappedBy']),
        //                    'ManyToOne'  => $this->addManyToOne($key, $value['joinColumns'][0], $value['targetEntity']),
        //                    'OneToMany'  => $this->addOneToMany($key, $value['targetEntity'], $value['mappedBy']),
        //                    'OneToOne'   => $this->addOneToOne($key, $value['joinColumns'], $value['targetEntity'], $value['mappedBy']),
        //                    default      => null
        //                };
        //            }
        //        }
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

    /**
     * @throws \Exception
     */
    public function addFields(array $fields): void
    {
        /** @var Column $value */
        foreach ($fields['columns'] as $value) {
            $field = new ColumnSchema(
                columnType: DataTypes::getType($value->getType()->getName()),
                filterOperators: FrontendFilterable::getRequiredOperators(DataTypes::getType($value->getType()->getName())),
                isPrimaryKey: in_array($value->getName(), $fields['primaries'], true),
                isReadOnly: false,
                isSortable: true,
                type: 'Column',
                defaultValue: $value->getDefault(),
            );

            $this->addField($value->getName(), $field);
        }
    }
}
