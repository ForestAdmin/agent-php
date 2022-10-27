<?php

namespace ForestAdmin\AgentPHP\Agent\Builder;

use Closure;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Computed\ComputedDefinition;

class CollectionBuilder
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

    public function addField(string $fieldName, ComputedDefinition $definition)
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

    public function addSegment(string $name, $definition)
    {
    }

    public function emulateFieldSorting($name)
    {
    }

    public function replaceFieldSorting($name, $equivalentSort)
    {
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

    public function replaceSearch(Closure $closure)
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
