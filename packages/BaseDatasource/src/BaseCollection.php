<?php

namespace ForestAdmin\AgentPHP\BaseDatasource;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\FrontendFilterable;
use ForestAdmin\AgentPHP\Agent\Utils\QueryAggregate;
use ForestAdmin\AgentPHP\Agent\Utils\QueryConverter;
use ForestAdmin\AgentPHP\BaseDatasource\Contracts\BaseDatasourceContract;
use ForestAdmin\AgentPHP\BaseDatasource\Utils\DataTypes;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection as ForestCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use Illuminate\Support\Arr;

class BaseCollection extends ForestCollection
{
    public function __construct(protected BaseDatasourceContract $datasource, string $name, protected string $tableName)
    {
        parent::__construct($datasource, $name);

        $fields = $this->fetchFieldsFromTable();
        $this->makeColumns($fields);
    }

    protected function fetchFieldsFromTable(): array
    {
        /** @var Table $rawFields */
        $table = $this->datasource->getOrm()->getDatabaseManager()->getDoctrineSchemaManager()->introspectTable($this->tableName);
        $primaries = [];

        foreach ($table->getIndexes() as $index) {
            if ($index->isPrimary()) {
                $primaries[] = $index->getColumns();
            }
        }

        return [
            'columns'   => $table->getColumns(),
            'primaries' => Arr::flatten($primaries),
        ];
    }

    protected function makeColumns(array $fields): void
    {
        /** @var Column $value */
        foreach ($fields['columns'] as $value) {
            $field = new ColumnSchema(
                columnType: DataTypes::getType($value->getType()->getName()),
                filterOperators: FrontendFilterable::getRequiredOperators(DataTypes::getType($value->getType()->getName())),
                isPrimaryKey: in_array($value->getName(), $fields['primaries'], true),
                isReadOnly: $value->getAutoincrement(), // if it's autoincrement the column is read only
                isSortable: true,
                type: 'Column',
                defaultValue: $value->getDefault(),
            );

            $this->addField($value->getName(), $field);
        }
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
            conditionTree: ConditionTreeFactory::matchIds($this, [[$id]])
        );

        return Arr::dot(QueryConverter::of($this, $caller->getTimezone(), $filter)->getQuery()->first());
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
