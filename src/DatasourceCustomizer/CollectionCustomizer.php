<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer;

use Closure;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\BaseAction;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Computed\ComputedDefinition;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DecoratorsStack;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\OperatorsEmulate\OperatorsEmulateCollection;

class CollectionCustomizer
{
    public function __construct(private DecoratorsStack $stack, private string $name)
    {
    }

    public function disableCount(): self
    {
        $this->stack->schema->getCollection($this->name)->overrideSchema('countable', false);

        return $this;
    }

    public function importField(string $name, array $options)
    {
    }

    public function renameField($oldName, string $newName)
    {
    }

    public function removeField(...$names)
    {
    }

    public function addAction(string $name, BaseAction $definition)
    {
        $this->stack->action->getCollection($this->name)->addAction($name, $definition);

        return $this;
    }

    public function addField(string $fieldName, ComputedDefinition $definition): self
    {
        $collection = $definition->isBeforeRelation()
            ? $this->stack->earlyComputed->getCollection($this->name)
            : $this->stack->lateComputed->getCollection($this->name);

        $collection->registerComputed($fieldName, $definition);

        return $this;
    }

    public function addFieldValidation(string $name, string $operator, $value = null): self
    {
        $this->stack->validation->getCollection($this->name)->addValidation($name, compact('operator', 'value'));

        return $this;
    }

    public function addManyToOneRelation(string $name, string $foreignCollection, string $foreignKey, ?string $foreignKeyTarget = null)
    {
        $this->pushRelation(
            $name,
            [
                'type'              => 'ManyToOne',
                'foreignCollection' => $foreignCollection,
                'foreignKey'        => $foreignKey,
                'foreignKeyTarget'  => $foreignKeyTarget,
            ]
        );

        return $this;
    }

    public function addOneToManyRelation(string $name, string $foreignCollection, string $originKey, ?string $originKeyTarget = null): self
    {
        $this->pushRelation(
            $name,
            [
                'type'              => 'OneToMany',
                'foreignCollection' => $foreignCollection,
                'originKey'         => $originKey,
                'originKeyTarget'   => $originKeyTarget,
            ]
        );

        return $this;
    }

    public function addOneToOneRelation(string $name, string $foreignCollection, string $originKey, ?string $originKeyTarget = null)
    {
        $this->pushRelation(
            $name,
            [
                'type'              => 'OneToOne',
                'foreignCollection' => $foreignCollection,
                'originKey'         => $originKey,
                'originKeyTarget'   => $originKeyTarget,
            ]
        );

        return $this;
    }

    public function addManyToManyRelation(
        string  $name,
        string  $foreignCollection,
        string  $throughTable,
        string  $throughCollection,
        string  $originKey,
        string  $foreignKey,
        ?string $originKeyTarget = null,
        ?string $foreignKeyTarget = null
    ) {
        $this->pushRelation(
            $name,
            [
                'type'              => 'ManyToMany',
                'foreignCollection' => $foreignCollection,
                'throughTable'      => $throughTable,
                'throughCollection' => $throughCollection,
                'originKey'         => $originKey,
                'originKeyTarget'   => $originKeyTarget,
                'foreignKey'        => $foreignKey,
                'foreignKeyTarget'  => $foreignKeyTarget,
            ]
        );

        return $this;
    }

    public function addExternalRelation(string $name, $definition)
    {
    }

    public function addSegment(string $name, \Closure $definition): self
    {
        $this->stack->segment->getCollection($this->name)->addSegment($name, $definition);

        return $this;
    }

    public function emulateFieldSorting($name): self
    {
        $this->stack->sort->getCollection($this->name)->replaceFieldSorting($name, null);

        return $this;
    }

    public function replaceFieldSorting($name, $equivalentSort): self
    {
        $this->stack->sort->getCollection($this->name)->replaceFieldSorting($name, $equivalentSort);

        return $this;
    }

    public function emulateFieldFiltering($name)
    {
    }

    public function emulateFieldOperator(string $name, string $operator): self
    {
        /** @var OperatorsEmulateCollection $collection */
        $collection = $this->stack->earlyOpEmulate->getCollection($this->name)->getFields()->get($name)
            ? $this->stack->earlyOpEmulate->getCollection($this->name)
            : $this->stack->lateOpEmulate->getCollection($name);

        $collection->emulateFieldOperator($name, $operator);

        return $this;
    }

    public function replaceFieldOperator(string $name, string $operator, ?\Closure $replaceBy = null): self
    {
        /** @var OperatorsEmulateCollection $collection */
        $collection = $this->stack->earlyOpEmulate->getCollection($this->name)->getFields()->get($name)
            ? $this->stack->earlyOpEmulate->getCollection($this->name)
            : $this->stack->lateOpEmulate->getCollection($name);

        $collection->replaceFieldOperator($name, $operator, $replaceBy);

        return $this;
    }

    public function replaceFieldWriting(string $name, \Closure $definition): self
    {
        $this->stack->write->getCollection($this->name)->replaceFieldWriting($name, $definition);

        return $this;
    }

    public function replaceSearch(Closure $closure): self
    {
        $this->stack->search->getCollection($this->name)->replaceSearch($closure);

        return $this;
    }

    public function addHook()
    {
    }

    public function addChart(string $name, Closure $definition): self
    {
        $this->stack->chart->getCollection($this->name)->addChart($name, $definition);

        return $this;
    }

    private function pushRelation(string $name, array $definition): self
    {
        $this->stack->relation->getCollection($this->name)->addRelation($name, $definition);

        return $this;
    }
}
