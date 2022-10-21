<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit;

use ForestAdmin\AgentPHP\Agent\Serializer\Transformers\BasicArrayTransformer;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Results\ActionResult;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ActionSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\RelationSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use Illuminate\Support\Collection as IlluminateCollection;

class Collection implements CollectionContract
{
    use CollectionMethods;

    protected string $className;

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

    public function getClassName(): string
    {
        return $this->className;
    }

    public function execute(Caller $caller, string $name, array $formValues, ?Filter $filter = null): ActionResult
    {
        // TODO: Implement execute() method.
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
        // TODO: Implement create() method.
    }

    public function show(Caller $caller, Filter $filter, $id, Projection $projection)
    {
        // TODO: Implement show() method.
    }

    public function list(Caller $caller, Filter $filter, Projection $projection): array
    {
        // TODO: Implement list() method.
    }

    public function export(Caller $caller, Filter $filter, Projection $projection): array
    {
        // TODO: Implement list() method.
    }

    public function update(Caller $caller, Filter $filter, $id, array $patch)
    {
        // TODO: Implement update() method.
    }

    public function delete(Caller $caller, Filter $filter, $id): void
    {
        // TODO: Implement delete() method.
    }

    public function aggregate(Caller $caller, Filter $filter, Aggregation $aggregation, ?int $limit = null, ?string $chartType = null)
    {
        // TODO: Implement aggregate() method.
    }

    public function associate(Caller $caller, Filter $parentFilter, Filter $childFilter, OneToManySchema|ManyToManySchema $relation): void
    {
        // TODO: Implement create() method.
    }

    public function dissociate(Caller $caller, Filter $parentFilter, Filter $childFilter, OneToManySchema|ManyToManySchema $relation): void
    {
        // TODO: Implement dissociate() method.
    }

    public function makeTransformer()
    {
        return new BasicArrayTransformer();
    }

    public function addFields(array $fields): void
    {
        foreach ($fields as $key => $value) {
            $this->addField($key, $value);
        }
    }

    /**
     * @throws ForestException
     */
    public function addField(string $name, ColumnSchema|RelationSchema $field): void
    {
        if ($this->fields->has($name)) {
            throw new ForestException('Field ' . $name . ' already defined in collection');
        }

        $this->fields->put($name, $field);
    }

    public function toArray($record): array
    {
        // by default $record is an array
        return $record;
    }
}
