<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Computed;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\CollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\FieldValidator;
use Illuminate\Support\Str;

class ComputedCollection extends CollectionDecorator
{
//    override readonly dataSource: DataSourceDecorator<ComputedCollection>;
//    protected computeds: Record<string, ComputedDefinition> = {};
    protected array $computeds;

    public function getComputed(string $path) //ComputedDefinition
    {
        if (! Str::contains($path, ':')) {
            return $this->computeds[$path];
        } else {
            $foreignCollection = $this->getSchema()->get(Str::before($path, ':'));
            $association = $this->dataSource->getCollection($foreignCollection);

            return $association->getComputed(Str::after($path, ':'));
        }
    }

    public function registerComputed(string $name, /*ComputedDefinition*/ $computed): void
    {
        foreach ($computed->dependencies as $field) {
            FieldValidator::validate($this, $field);
        }

        if (count($computed->dependencies) === 0) {
            throw new ForestException('Computed field ' . $this->getName() . '.' . $name . ' must have at least one dependency.');
        }

        $this->computeds[$name] = $computed;
        $this->markSchemaAsDirty();
    }

    public function list(Caller $caller, PaginatedFilter $filter, Projection $projection): array
    {
        $childProjection = $projection->replace(/*path => rewriteField(this, path)*/);
        $records = $this->childCollection->list($caller, $filter, $childProjection);
        $context = null; /*new CollectionCustomizationContext($this, $caller);*/

        /*return computeFromRecords($context, $this, $childProjection, $projection, $records);*/
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
                'columnType' =>  $computed['columnType'],
                'defaultValue' =>  $computed['defaultValue'],
                'enumValues' =>  $computed['enumValues'],
                'filterOperators' => '', /*new Set()*/
                'isPrimaryKey' =>  false,
                'isReadOnly' =>  true,
                'isSortable' =>  false,
                'type' =>  'Column',
            ];
        }

        return $schema;
    }

}
