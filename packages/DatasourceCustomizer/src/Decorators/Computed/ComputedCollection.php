<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Computed;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Context\CollectionCustomizationContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\CollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Computed\Utils\ComputeField;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\RelationSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\FieldValidator;
use Illuminate\Support\Collection as IlluminateCollection;
use Illuminate\Support\Str;

class ComputedCollection extends CollectionDecorator
{
    protected array $computeds = [];

    public function getComputed(string $path): ?ComputedDefinition
    {
        if (! Str::contains($path, ':')) {
            return $this->computeds[$path] ?? null;
        } else {
            $schema = $this->getFields()->get(Str::before($path, ':'));
            $association = $this->dataSource->getCollection($schema->getForeignCollection());

            return $association->getComputed(Str::after($path, ':'));
        }
    }

    public function refineSchema(IlluminateCollection $childSchema): IlluminateCollection
    {
        $schema = $childSchema;
        foreach ($this->computeds as $fieldName => $computed) {
            $schema->put(
                $fieldName,
                new ColumnSchema(
                    columnType: $computed->getColumnType(),
                    isReadOnly: true,
                    defaultValue: $computed->getDefaultValue(),
                    enumValues: $computed->getEnumValues()
                )
            );
        }

        return $schema;
    }

    public function registerComputed(string $name, ComputedDefinition $computed): void
    {
        foreach ($computed->getDependencies() as $field) {
            FieldValidator::validate($this, $field);
        }

        if (count($computed->getDependencies()) === 0) {
            throw new ForestException('Computed field ' . $this->getName() . '.' . $name . ' must have at least one dependency.');
        }

        $this->computeds[$name] = $computed;
        $this->markSchemaAsDirty();
    }

    public function list(Caller $caller, PaginatedFilter $filter, Projection $projection): array
    {
        $childProjection = $projection->replaceItem(fn ($path) => $this->rewriteField($this, $path));
        $records = $this->childCollection->list($caller, $filter, $childProjection);
        $context = new CollectionCustomizationContext($this, $caller);

        return ComputeField::computeFromRecords($context, $this, $childProjection, $projection, $records);
    }

    public function aggregate(Caller $caller, Filter $filter, Aggregation $aggregation, ?int $limit = null)
    {
        if (! $aggregation->getProjection()->some(fn ($field) => $this->getComputed($field))) {
            return $this->childCollection->aggregate($caller, $filter, $aggregation, $limit);
        }

        $filter = new PaginatedFilter($filter->getConditionTree(), $filter->getSearch(), $filter->getSearchExtended(), $filter->getSegment());

        return $aggregation->apply(
            $this->list($caller, $filter, $aggregation->getProjection()->withPks($this)),
            $caller->getTimezone(),
            $limit
        );
    }

    protected function rewriteField(ComputedCollection $collection, string $path): Projection
    {
        if (Str::contains($path, ':')) {
            $prefix = explode(':', $path);
            /** @var RelationSchema $schema */
            $schema = $collection->getFields()->get($prefix[0]);
            $association = $collection->getDataSource()->getCollection($schema->getForeignCollection());

            return (new Projection($path))
                ->unnest()
                ->replaceItem(fn ($subPath) => $this->rewriteField($association, $subPath))
                ->nest($prefix[0]);
        }

        $computed = $collection->getComputed($path);

        return $computed
            ? (new Projection($computed->getDependencies()))->replaceItem(fn ($depPath) => $this->rewriteField($collection, $depPath))
            : new Projection($path);
    }
}
