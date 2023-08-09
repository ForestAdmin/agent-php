<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer;

use Closure;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\BaseAction;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Computed\ComputedDefinition;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DecoratorsStack;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\OperatorsEmulate\OperatorsEmulateCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Plugins\AddExternalRelation;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Plugins\ImportField;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\Rules;
use Illuminate\Support\Collection as IlluminateCollection;

class CollectionCustomizer
{
    public function __construct(private DatasourceCustomizer $datasourceCustomizer, private DecoratorsStack $stack, private string $name)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSchema(): IlluminateCollection
    {
        return $this->stack->validation->getCollection($this->name)->getSchema();
    }

    public function getCollection(): CollectionContract
    {
        return $this->stack->validation->getCollection($this->name);
    }

    public function disableCount(): self
    {
        return $this->pushCustomization(
            fn () => $this->stack->schema->getCollection($this->name)->overrideSchema('countable', false)
        );
    }

    public function importField(string $name, array $options): self
    {
        return $this->use(ImportField::class, array_merge(['name' => $name], $options));
    }

    /**
     * Allow to rename a field of a given collection.
     *
     * @param string $oldName the current name of the field in a given collection
     * @param string $newName the new name of the field
     */
    public function renameField(string $oldName, string $newName)
    {
        return $this->pushCustomization(
            fn () => $this->stack->renameField->getCollection($this->name)->renameField($oldName, $newName)
        );
    }

    public function removeField(...$fields)
    {
        return $this->pushCustomization(
            function () use ($fields) {
                foreach ($fields as $field) {
                    $this->stack->publication->getCollection($this->name)->changeFieldVisibility($field, false);
                }
            }
        );
    }

    public function addAction(string $name, BaseAction $definition)
    {
        return $this->pushCustomization(
            fn () => $this->stack->action->getCollection($this->name)->addAction($name, $definition)
        );
    }

    public function addField(string $fieldName, ComputedDefinition $definition): self
    {
        return $this->pushCustomization(
            function () use ($fieldName, $definition) {
                $collection = $definition->isBeforeRelation()
                    ? $this->stack->earlyComputed->getCollection($this->name)
                    : $this->stack->lateComputed->getCollection($this->name);

                $collection->registerComputed($fieldName, $definition);
            }
        );
    }

    public function addFieldValidation(string $name, string $operator, $value = null): self
    {
        return $this->pushCustomization(
            fn () => $this->stack
                ->validation
                ->getCollection($this->name)
                ->addValidation($name, ['operator' => $operator, 'value' => $value])
        );
    }

    public function addManyToOneRelation(string $name, string $foreignCollection, string $foreignKey, ?string $foreignKeyTarget = null)
    {
        return $this->pushCustomization(
            fn () => $this->pushRelation(
                $name,
                [
                    'type'              => 'ManyToOne',
                    'foreignCollection' => $foreignCollection,
                    'foreignKey'        => $foreignKey,
                    'foreignKeyTarget'  => $foreignKeyTarget,
                ]
            )
        );
    }

    public function addOneToManyRelation(string $name, string $foreignCollection, string $originKey, ?string $originKeyTarget = null): self
    {
        return $this->pushCustomization(
            fn () => $this->pushRelation(
                $name,
                [
                    'type'              => 'OneToMany',
                    'foreignCollection' => $foreignCollection,
                    'originKey'         => $originKey,
                    'originKeyTarget'   => $originKeyTarget,
                ]
            )
        );
    }

    public function addOneToOneRelation(string $name, string $foreignCollection, string $originKey, ?string $originKeyTarget = null)
    {
        return $this->pushCustomization(
            fn () => $this->pushRelation(
                $name,
                [
                    'type'              => 'OneToOne',
                    'foreignCollection' => $foreignCollection,
                    'originKey'         => $originKey,
                    'originKeyTarget'   => $originKeyTarget,
                ]
            )
        );
    }

    public function addManyToManyRelation(
        string  $name,
        string  $foreignCollection,
        string  $throughCollection,
        string  $originKey,
        string  $foreignKey,
        ?string $originKeyTarget = null,
        ?string $foreignKeyTarget = null
    ) {
        return $this->pushCustomization(
            fn () => $this->pushRelation(
                $name,
                [
                    'type'              => 'ManyToMany',
                    'foreignCollection' => $foreignCollection,
                    'throughCollection' => $throughCollection,
                    'originKey'         => $originKey,
                    'originKeyTarget'   => $originKeyTarget,
                    'foreignKey'        => $foreignKey,
                    'foreignKeyTarget'  => $foreignKeyTarget,
                ]
            )
        );
    }

    public function use(string $plugin, array $options = []): self
    {
        return $this->pushCustomization(
            fn () => (new $plugin())->run($this->datasourceCustomizer, $this, $options)
        );
    }

    public function addExternalRelation(string $name, array $definition): self
    {
        return $this->use(AddExternalRelation::class, array_merge(['name' => $name], $definition));
    }

    public function addSegment(string $name, \Closure $definition): self
    {
        return $this->pushCustomization(
            fn () => $this->stack->segment->getCollection($this->name)->addSegment($name, $definition)
        );
    }

    public function emulateFieldSorting($name): self
    {
        return $this->pushCustomization(
            fn () => $this->stack->sort->getCollection($this->name)->replaceFieldSorting($name, null)
        );
    }

    public function replaceFieldBinaryMode($name, $binaryMode): self
    {
        return $this->pushCustomization(
            fn () => $this->stack->binary->getCollection($this->name)->setBinaryMode($name, $binaryMode)
        );
    }

    public function replaceFieldSorting($name, $equivalentSort): self
    {
        return $this->pushCustomization(
            fn () => $this->stack->sort->getCollection($this->name)->replaceFieldSorting($name, $equivalentSort)
        );
    }

    /**
     * Enable filtering on a specific field using emulation.
     * As for all the emulation method, the field filtering will be done in-memory.
     * @param string $name the name of the field to enable emulation on
     * @example emulateFieldFiltering('aField');
     */
    public function emulateFieldFiltering($name): self
    {
        return $this->pushCustomization(
            function () use ($name) {
                $collection = $this->stack->lateOpEmulate->getCollection($this->name);
                /** @var ColumnSchema $field */
                $field = $collection->getFields()[$name];
                if (is_string($field->getColumnType())) {
                    $operators = Rules::getAllowedOperatorsForColumnType($field->getColumnType());
                    foreach ($operators as $operator) {
                        if (! in_array($operator, $field->getFilterOperators(), true)) {
                            $this->emulateFieldOperator($name, $operator);
                        }
                    }
                }
            }
        );
    }

    public function emulateFieldOperator(string $name, string $operator): self
    {
        return $this->pushCustomization(
            function () use ($name, $operator) {
                /** @var OperatorsEmulateCollection $collection */
                $collection = $this->stack->earlyOpEmulate->getCollection($this->name)->getFields()->get($name)
                    ? $this->stack->earlyOpEmulate->getCollection($this->name)
                    : $this->stack->lateOpEmulate->getCollection($this->name);

                $collection->emulateFieldOperator($name, $operator);
            }
        );
    }

    public function replaceFieldOperator(string $name, string $operator, ?\Closure $replaceBy = null): self
    {
        return $this->pushCustomization(
            function () use ($name, $operator, $replaceBy) {
                /** @var OperatorsEmulateCollection $collection */
                $collection = $this->stack->earlyOpEmulate->getCollection($this->name)->getFields()->get($name)
                    ? $this->stack->earlyOpEmulate->getCollection($this->name)
                    : $this->stack->lateOpEmulate->getCollection($this->name);

                $collection->replaceFieldOperator($name, $operator, $replaceBy);
            }
        );
    }

    public function replaceFieldWriting(string $name, ?\Closure $definition): self
    {
        return $this->pushCustomization(
            fn () => $this->stack->write->getCollection($this->name)->replaceFieldWriting($name, $definition)
        );
    }

    public function replaceSearch(Closure $closure): self
    {
        return $this->pushCustomization(
            fn () => $this->stack->search->getCollection($this->name)->replaceSearch($closure)
        );
    }

    public function addHook(string $position, string $type, Closure $handler)
    {
        return $this->pushCustomization(
            fn () => $this->stack->hook->getCollection($this->name)->addHook($position, $type, $handler)
        );
    }

    public function addChart(string $name, Closure $definition): self
    {
        return $this->pushCustomization(
            fn () => $this->stack->chart->getCollection($this->name)->addChart($name, $definition)
        );
    }

    private function pushRelation(string $name, array $definition): self
    {
        return $this->pushCustomization(
            fn () => $this->stack->relation->getCollection($this->name)->addRelation($name, $definition)
        );
    }

    private function pushCustomization(Closure $customization): self
    {
        $this->stack->queueCustomization($customization);

        return $this;
    }
}
