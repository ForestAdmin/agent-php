<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Decorators;

use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\GeneratorCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Results\ActionResult;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\OneToManySchema;

class CollectionDecorator implements CollectionContract
{
    private array $lastSchema; //: CollectionSchema;
    private array $lastSubSchema;  //: CollectionSchema;

    public function __construct(protected Collection $childCollection, protected Datasource $dataSource)
    {
    }

    public function getSchema(): CollectionSchema
    {
        $subSchema = GeneratorCollection::buildSchema($this->childCollection); // const subSchema = this.childCollection.schema;

        if (! $this->lastSchema || $this->lastSubSchema !== $subSchema) {
            $this->lastSchema = $this->refineSchema($subSchema);
            $this->lastSubSchema = $subSchema;
        }

        return $this->lastSchema;
    }

    public function execute(Caller $caller, string $name, array $data, ?Filter $filter = null): ActionResult
    {
        $refinedFilter = $this->refineFilter($caller, $filter);

        return $this->childCollection->execute($caller, $name, $data, $refinedFilter);
    }

    public function getForm(Caller $caller, string $name, ?array $data = null, ?Filter $filter = null): array
    {
        $refinedFilter = $this->refineFilter($caller, $filter);

        return $this->childCollection->getForm($caller, $name, $data, $refinedFilter);
    }

    public function create(Caller $caller, array $data)
    {
        return $this->childCollection->create($caller, $data);
    }

    public function list(Caller $caller, PaginatedFilter $filter, Projection $projection): array
    {
        $refinedFilter = $this->refineFilter($caller, $filter);

        return $this->childCollection->list($caller, $refinedFilter, $projection);
    }

    public function update(Caller $caller, Filter $filter, $id, array $patch)
    {
        $refinedFilter = $this->refineFilter($caller, $filter);

        return $this->childCollection->update($caller, $refinedFilter, $id, $patch);
    }

    public function delete(Caller $caller, Filter $filter, $id): void
    {
        $refinedFilter = $this->refineFilter($caller, $filter);

        $this->childCollection->delete($caller, $refinedFilter, $id);
    }

    public function aggregate(Caller $caller, Filter $filter, Aggregation $aggregation, ?int $limit = null, ?string $chartType = null)
    {
        $refinedFilter = $this->refineFilter($caller, $filter);

        $this->childCollection->aggregate($caller, $refinedFilter, $aggregation, $limit);
    }

    protected function markSchemaAsDirty(): void
    {
        $this->lastSchema = null;
    }

    protected function refineFilter(Caller $caller, ?PaginatedFilter $filter): ?PaginatedFilter
    {
        return $filter;
    }

    protected function refineSchema($subSchema /*: CollectionSchema*/) /* CollectionSchema*/
    {
        return $subSchema;
    }

///// METHODS of CollectionContract
    public function getDataSource(): DatasourceContract
    {
        // TODO: Implement getDataSource() method.
    }

    public function getName(): string
    {
        // TODO: Implement getName() method.
    }

    public function getClassName(): string
    {
        // TODO: Implement getClassName() method.
    }

    public function show(Caller $caller, PaginatedFilter $filter, $id, Projection $projection)
    {
        // TODO: Implement show() method.
    }

    public function export(Caller $caller, PaginatedFilter $filter, Projection $projection): array
    {
        // TODO: Implement export() method.
    }

    public function associate(Caller $caller, Filter $parentFilter, Filter $childFilter, OneToManySchema|ManyToManySchema $relation): void
    {
        // TODO: Implement associate() method.
    }

    public function dissociate(Caller $caller, Filter $parentFilter, Filter $childFilter, OneToManySchema|ManyToManySchema $relation): void
    {
        // TODO: Implement dissociate() method.
    }
}
