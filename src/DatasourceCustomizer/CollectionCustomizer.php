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

    public function addManyToOneRelation()
    {
    }

    public function addOneToManyRelation()
    {
    }

    public function addOneToOneRelation()
    {
    }

    public function addManyToManyRelation()
    {
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

    private function addRelation(string $name, $definition)
    {
    }
}
