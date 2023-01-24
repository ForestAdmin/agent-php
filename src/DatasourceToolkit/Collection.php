<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit;

use ForestAdmin\AgentPHP\Agent\Serializer\Transformers\BaseTransformer;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Results\ActionResult;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use Illuminate\Support\Collection as IlluminateCollection;

class Collection implements CollectionContract
{
    use CollectionMethods;

    protected string $transformer;

    public function __construct(
        protected DatasourceContract $dataSource,
        protected string $name,
    ) {
        $this->fields = new IlluminateCollection();
        $this->actions = new IlluminateCollection();
        $this->segments = new IlluminateCollection();
    }

    public function getDataSource(): DatasourceContract
    {
        return $this->dataSource;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function execute(Caller $caller, string $name, array $formValues, ?Filter $filter = null): ActionResult
    {
        if (! $this->actions->get($name)) {
            throw new ForestException("Action $name is not implemented.");
        }

        // TODO QUESTION HOW TO RETURN ACTIONRESULT + CHECK DUMMYDATA SOURCE PARAMETERS ARE MISSING ? (base.ts -> override async execute(): Promise<ActionResult>)
    }

    public function getForm(Caller $caller, string $name, ?array $formValues = null, ?Filter $filter = null): array
    {
        return [];
    }

    public function create(Caller $caller, array $data)
    {
    }

    public function show(Caller $caller, Filter $filter, $id, Projection $projection)
    {
    }

    public function list(Caller $caller, Filter $filter, Projection $projection): array
    {
    }

    public function update(Caller $caller, Filter $filter, array $patch)
    {
    }

    public function delete(Caller $caller, Filter $filter): void
    {
    }

    public function aggregate(Caller $caller, Filter $filter, Aggregation $aggregation, ?int $limit = null, ?string $chartType = null)
    {
    }

    public function makeTransformer()
    {
        return new BaseTransformer($this->getName());
    }

    public function toArray($record, ?Projection $projection = null): array
    {
        // by default $record is an array
        return $record;
    }
}
