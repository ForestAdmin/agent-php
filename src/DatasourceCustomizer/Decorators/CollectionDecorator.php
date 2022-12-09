<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators;

use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\GeneratorCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\CollectionMethods;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Results\ActionResult;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\RelationSchema;
use Illuminate\Support\Collection as IlluminateCollection;

class CollectionDecorator implements CollectionContract
{
    use CollectionMethods;

    private ?array $lastSchema; //: CollectionSchema;

    private array $lastSubSchema;  //: CollectionSchema;

    public function __construct(protected CollectionContract|CollectionDecorator $childCollection, protected Datasource $dataSource)
    {
        $this->fields = new IlluminateCollection();
        $this->actions = new IlluminateCollection();
        $this->segments = new IlluminateCollection();
    }

    public function isSearchable(): bool
    {
        return $this->childCollection->isSearchable();
    }

    public function getFields(): IlluminateCollection
    {
        return $this->childCollection->getFields();
    }

    public function putField(string $name, ColumnSchema|RelationSchema $field): IlluminateCollection
    {
        return $this->childCollection->putField($name, $field);
    }

    public function getSchema(): IlluminateCollection
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

    public function update(Caller $caller, Filter $filter, array $patch)
    {
        $refinedFilter = $this->refineFilter($caller, $filter);

        return $this->childCollection->update($caller, $refinedFilter, $patch);
    }

    public function delete(Caller $caller, Filter $filter): void
    {
        $refinedFilter = $this->refineFilter($caller, $filter);

        $this->childCollection->delete($caller, $refinedFilter);
    }

    public function aggregate(Caller $caller, Filter $filter, Aggregation $aggregation, ?int $limit = null, ?string $chartType = null)
    {
        $refinedFilter = $this->refineFilter($caller, $filter);

        return $this->childCollection->aggregate($caller, $refinedFilter, $aggregation, $limit, $chartType);
    }

    protected function markSchemaAsDirty(): void
    {
        $this->lastSchema = null;
    }

    protected function refineFilter(Caller $caller, Filter|PaginatedFilter|null $filter): Filter|PaginatedFilter|null
    {
        return $filter;
    }

    protected function refineSchema($subSchema /*: CollectionSchema*/) /* CollectionSchema*/
    {
        return $subSchema;
    }

    public function getName(): string
    {
        return $this->childCollection->getName();
    }

    public function makeTransformer()
    {
        return $this->childCollection->makeTransformer();
    }

    public function toArray($record): array
    {
        return $this->childCollection->toArray($record);
    }

    public function getDataSource(): DatasourceContract
    {
        return $this->dataSource;
    }

    public function getClassName(): string
    {
        return $this->childCollection->getClassName();
    }

    public function show(Caller $caller, Filter $filter, $id, Projection $projection)
    {
        return $this->childCollection->show($caller, $filter, $id, $projection);
    }

    public function getSegments()
    {
        return $this->childCollection->getSegments();
    }
}
