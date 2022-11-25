<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer;

use Closure;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Computed\ComputedDefinition;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DecoratorsStack;

class CollectionCustomizer
{
    public function __construct(private DecoratorsStack $stack, private string $name)
    {
    }

    public function disableCount()
    {
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

    public function addAction(string $name, $definition)
    {
    }

    public function addField(string $fieldName, ComputedDefinition $definition): self
    {
        $collection = $definition->isBeforeRelation()
            ? $this->stack->earlyComputed->getCollection($this->name)
            : $this->stack->lateComputed->getCollection($this->name);

        $collection->registerComputed($fieldName, $definition);

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
                'type'                   => 'ManyToMany',
                'foreignCollection'      => $foreignCollection,
                'throughTable'           => $throughTable,
                'throughCollection'      => $throughCollection,
                'originKey'              => $originKey,
                'originKeyTarget'        => $originKeyTarget,
                'foreignKey'             => $foreignKey,
                'foreignKeyTarget'       => $foreignKeyTarget,
            ]
        );

        return $this;
    }

    public function addExternalRelation(string $name, $definition)
    {
    }

    public function addSegment(string $name, \Closure $definition)
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

    public function emulateFieldOperator($name, $operator)
    {
    }

    public function replaceFieldOperator()
    {
    }

    public function replaceFieldWriting()
    {
    }

    public function replaceSearch(Closure $closure): self
    {
        $this->stack
            ->search
            ->getCollection($this->name)
            ->replaceSearch($closure);

        return $this;
    }

    public function addHook()
    {
    }

    private function pushRelation(string $name, array $definition): self
    {
        $this->stack->relation->getCollection($this->name)->addRelation($name, $definition);

        return $this;
    }
}
