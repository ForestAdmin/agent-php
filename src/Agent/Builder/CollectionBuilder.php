<?php

namespace ForestAdmin\AgentPHP\Agent\Builder;

class CollectionBuilder
{
    public function __construct(private $stack, private $name)
    {}

    public function disableCount()
    {}

    public function importField(string $name, array $options)
    {}

    public function renameField($oldName, string $newName)
    {}

    public function removeField(...$names)
    {}

    public function addAction(string $name, $definition)
    {}

    public function addField(string $name, $definition)
    {}

    public function addManyToOneRelation()
    {}

    public function addOneToManyRelation()
    {}

    public function addOneToOneRelation()
    {}

    public function addManyToManyRelation()
    {}

    public function addExternalRelation(string $name, $definition)
    {}

    public function addSegment(string $name, $definition)
    {}

    public function emulateFieldSorting($name)
    {}

    public function replaceFieldSorting($name, $equivalentSort)
    {}

    public function emulateFieldFiltering($name)
    {}

    public function emulateFieldOperator($name, $operator)
    {}

    public function replaceFieldOperator()
    {}

    public function replaceFieldWriting()
    {}

    public function replaceSearch($definition)
    {}

    public function addHook()
    {}

    private function addRelation(string $name, $definition)
    {}
}
