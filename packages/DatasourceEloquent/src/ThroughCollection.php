<?php

namespace ForestAdmin\AgentPHP\DatasourceEloquent;

use ForestAdmin\AgentPHP\BaseDatasource\BaseCollection;
use ForestAdmin\AgentPHP\BaseDatasource\Contracts\BaseDatasourceContract;
use ForestAdmin\AgentPHP\BaseDatasource\Utils\QueryConverter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Record as RecordUtils;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Schema as SchemaUtils;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ThroughCollection extends BaseCollection
{
    protected string $tableName;

    public function __construct(protected BaseDatasourceContract $datasource, protected array $metadata)
    {
        parent::__construct($datasource, $this->metadata['name'], $this->metadata['tableName']);

        $this->addRelations();
        $this->searchable = false;
    }

    protected function addRelations(): void
    {
        foreach ($this->metadata['relations'] as $relation) {
            $relation = new ManyToOneSchema(...$relation);
            $this->addField(Str::lower($relation->getForeignCollection()), $relation);
        }
    }

    /**
     * @param Caller $caller
     * @param array  $data
     * @return array|void
     * @codeCoverageIgnore
     */
    public function create(Caller $caller, array $data)
    {
        $query = QueryConverter::of($this, $caller->getTimezone())->getQuery();
        $primaryKeys = SchemaUtils::getPrimaryKeys($this);
        if (collect($primaryKeys)->every(fn ($value) => array_key_exists($value, $data))) {
            $query->insert($data);
            $filter = new Filter(
                conditionTree: ConditionTreeFactory::matchIds($this, [RecordUtils::getPrimaryKeys($this, $data)]),
            );
        } else {
            $id = $query->insertGetId($data, SchemaUtils::getPrimaryKeys($this)[0]);
            $filter = new Filter(
                conditionTree: new  ConditionTreeLeaf(SchemaUtils::getPrimaryKeys($this)[0], Operators::EQUAL, $id),
            );
        }

        return Arr::dot(QueryConverter::of($this, $caller->getTimezone(), $filter)->getQuery()->first());
    }
}
