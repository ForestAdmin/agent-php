<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Computed;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\CollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Computed\Utils\Flattener;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\RelationSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\FieldValidator;
use Illuminate\Support\Collection as IlluminateCollection;
use Illuminate\Support\Str;

class ComputedCollection extends CollectionDecorator
{
//    override readonly dataSource: DataSourceDecorator<ComputedCollection>;
//    protected computeds: Record<string, ComputedDefinition> = {};
    protected array $computeds = [];

    public function getComputed(string $path): ?ComputedDefinition
    {
        if (! Str::contains($path, ':')) {
            return $this->computeds[$path] ?? null;
        } else {
            $foreignCollection = $this->getSchema()->get(Str::before($path, ':'));
            $association = $this->dataSource->getCollection($foreignCollection);

            return $association->getComputed(Str::after($path, ':'));
        }
    }

    public function getFields(): IlluminateCollection
    {
        $fields = parent::getFields();
        /** @var ComputedDefinition $computed */
        foreach ($this->computeds as $fieldName => $computed) {
            $fields->put(
                $fieldName,
                new ColumnSchema(
                    columnType: $computed->getColumnType(),
                    isReadOnly: true,
                    defaultValue: $computed->getDefaultValue(),
                    enumValues: $computed->getEnumValues()
                )
            );
        }

        return $fields;
    }

    public function registerComputed(string $name, ComputedDefinition $computed): void
    {
//        foreach ($computed['dependencies'] as $field) {
//            FieldValidator::validate($this, $field);
//        }

        if (count($computed->getDependencies()) === 0) {
            throw new ForestException('Computed field ' . $this->getName() . '.' . $name . ' must have at least one dependency.');
        }

        $this->computeds[$name] = $computed;
        $this->markSchemaAsDirty();
    }

    public function list(Caller $caller, PaginatedFilter|Filter $filter, Projection $projection): array
    {
        //$childProjection = $projection->replace(/*path => rewriteField(this, path)*/);
        //$records = $this->childCollection->list($caller, $filter, $childProjection);
        //$context = null; /*new CollectionCustomizationContext($this, $caller);*/
        /*return computeFromRecords($context, $this, $childProjection, $projection, $records);*/
        $childProjection = $projection->replaceItem(fn ($path) => $this->rewriteField($this, $path));
        $records = $this->childCollection->list($caller, $filter, $childProjection);
//
//        $flatten = Flattener::flatten($records, $childProjection);
//        dd($records , Flattener::unFlatten($flatten, $childProjection));

//        /**
//         * @var string $fieldName
//         * @var ComputedDefinition $computedDefinition
//         */
//        foreach ($this->computeds as $fieldName => $computedDefinition) {
//            $records = call_user_func($computedDefinition->getValues(), $records);
//        }
//
//        dd($this->childCollection->toArray($records[0], $childProjection));

        return $records;
    }

    public function aggregate(Caller $caller, Filter $filter, Aggregation $aggregation, ?int $limit = null, ?string $chartType = null)
    {
        if (! $aggregation->getProjection()->some(/*field => this.getComputed(field))*/)) {
            return $this->childCollection->aggregate($caller, $filter, $aggregation, $limit);
        }

        return $aggregation->apply( /* faire methode apply sur Aggregation */
            $this->list($caller, $filter, $aggregation->getProjection()),
            $caller->getTimezone(),
            $limit
        );
    }

    protected function refineSchema($childSchema /*: CollectionSchema*/) /* CollectionSchema*/
    {
        $schema = array_merge(); //{ ...childSchema, fields: { ...childSchema.fields } };

        foreach (array_values($this->computeds) as $name => $computed) {
            $schema['fields'][$name] = [
                'columnType'      => $computed['columnType'],
                'defaultValue'    => $computed['defaultValue'],
                'enumValues'      => $computed['enumValues'],
                'filterOperators' => '', /*new Set()*/
                'isPrimaryKey'    => false,
                'isReadOnly'      => true,
                'isSortable'      => false,
                'type'            => 'Column',
            ];
        }

        return $schema;
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
            ? (new Projection(...$computed->getDependencies()))->replaceItem(fn ($depPath) => $this->rewriteField($collection, $depPath))
            : new Projection($path);
    }
}
