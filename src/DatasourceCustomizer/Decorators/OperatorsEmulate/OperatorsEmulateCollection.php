<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\OperatorsEmulate;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Context\CollectionCustomizationContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\CollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTree;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\RelationSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Schema as SchemaUtils;
use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\ConditionTreeValidator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\FieldValidator;
use Illuminate\Support\Collection as IlluminateCollection;
use Illuminate\Support\Str;

class OperatorsEmulateCollection extends CollectionDecorator
{
    private array $emulateOperators = [];

    public function emulateFieldOperator(string $name, string $operator): void
    {
        self::replaceFieldOperator($name, $operator, null);
    }

    public function replaceFieldOperator(string $name, string $operator, ?\Closure $replaceBy = null): void
    {
        $pks = SchemaUtils::getPrimaryKeys($this->childCollection);

        foreach ($pks as $pk) {
            /** @var ColumnSchema $schema */
            $schema = $this->childCollection->getFields()[$pk];
            $operators = $schema->getFilterOperators();

            if (! in_array('Equal', $operators, true) || ! in_array('In', $operators, true)) {
                throw new ForestException("Cannot override operators on collection $this->name: the primary key columns must support 'Equal' and 'In' operators");
            }
        }

        // Check that targeted field is valid
        /** @var ColumnSchema $schema */
        $field = $this->childCollection->getFields()[$name];
        if (! $field) {
            throw new ForestException('Cannot replace operator for relation');
        }
        FieldValidator::validate($this, $name);


        // Mark the field operator as replaced.
        if (! in_array($name, $this->emulateOperators, true)) {
            $this->emulateOperators[$name] = [];
        }

        $this->emulateOperators[$name][$operator] = $replaceBy;
        $this->markSchemaAsDirty();
    }

    public function getFields(): IlluminateCollection
    {
        $fields = $this->childCollection->getFields();

        /**
         * @var string $fieldName
         * @var ColumnSchema $schema
         */
        foreach ($fields as $fieldName => $schema) {
            if (array_key_exists($fieldName, $this->emulateOperators)) {
                $schema->setFilterOperators(
                    array_unique(
                        array_merge($schema->getFilterOperators(), array_keys($this->emulateOperators[$fieldName]))
                    )
                );
            }
        }

        return $fields;
    }

    protected function refineFilter(Caller $caller, Filter|PaginatedFilter|null $filter): Filter|PaginatedFilter|null
    {
        return $filter?->override(
            conditionTree: $filter->getConditionTree()?->replaceLeafs(fn ($leaf) => $this->replaceLeaf($caller, $leaf, []))
        );
    }

    private function replaceLeaf(Caller $caller, ConditionTreeLeaf $leaf, array $replacements): ConditionTree
    {
        if (Str::contains($leaf->getField(), ':')) {
            $prefix = Str::before($leaf->getField(), ':');
            /** @var RelationSchema $schema */
            $schema = $this->getFields()->get($prefix);
            $association = $this->dataSource->getCollection($schema->getForeignCollection());
            $associationLeaf = $leaf->unnest()->replaceLeafs(fn ($subLeaf) => $association->replaceLeaf($caller, $subLeaf, $replacements));

            return $associationLeaf->nest($prefix);
        }

        return isset($this->emulateOperators[$leaf->getField()]) && in_array($leaf->getOperator(), array_keys($this->emulateOperators[$leaf->getField()]), true)
            ? $this->computeEquivalent($caller, $leaf, $replacements)
            : $leaf;
    }

    private function computeEquivalent(Caller $caller, ConditionTreeLeaf $leaf, array $replacements): ConditionTree
    {
        if (isset($this->emulateOperators[$leaf->getField()][$leaf->getOperator()])) {
            $handler = $this->emulateOperators[$leaf->getField()][$leaf->getOperator()];
            $replacementId = $this->getName() . '.' . $leaf->getField() . '[' . $leaf->getOperator() . ']';
            $subReplacements = [...$replacements, $replacementId];

            if (in_array($replacementId, $replacements, true)) {
                throw new ForestException('Operator replacement cycle ' . implode(' -> ', $subReplacements));
            }

            $result = $handler($leaf->getValue(), new CollectionCustomizationContext($this, $caller));
            if ($result) {
                $equivalent = $result instanceof ConditionTree ? $result : ConditionTreeFactory::fromArray($result);

                $equivalent = $equivalent->replaceLeafs(fn ($subLeaf) => $this->replaceLeaf($caller, $subLeaf, $subReplacements));
                ConditionTreeValidator::validate($equivalent, $this);

                return $equivalent;
            }
        }

        return ConditionTreeFactory::matchRecords(
            $this,
            $leaf->apply(
                $this->list($caller, new PaginatedFilter(), $leaf->getProjection()->withPks($this)),
                $this,
                $caller->getTimezone()
            )
        );
    }
}
