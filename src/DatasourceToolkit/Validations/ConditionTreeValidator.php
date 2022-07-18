<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Validations;


use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTree;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\RelationSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Collection as CollectionUtils;

class ConditionTreeValidator
{
    /**
     * @throws \Exception
     */
    public static function validate(ConditionTree $conditionTree, Collection $collection): void
    {
        if ($conditionTree instanceof ConditionTreeBranch) {
            self::validateBranch($conditionTree, $collection);
        } elseif ($conditionTree instanceof ConditionTreeLeaf) {
            self::validateLeaf($conditionTree, $collection);
        } else {
            throw new \Exception('Unexpected condition tree type');
        }
    }

    /**
     * @throws \Exception
     */
    private static function validateBranch(ConditionTreeBranch $branch, Collection $collection): void
    {
        if (! in_array($branch->getAggregator(), ['And', 'Or'])){
            throw new \Exception('The given aggregator ' . $branch->getAggregator() . ' is not supported. The supported values are: [\'Or\', \'And\']');
        }

        foreach ($branch->getConditions() as $condition) {
            self::validate($condition, $collection);
        }
    }

    /**
     * @throws \Exception
     */
    private static function validateLeaf(ConditionTreeLeaf $leaf, Collection $collection): void
    {
        $fieldSchema = CollectionUtils::getFieldSchema($collection, $leaf->getField());

        self::throwIfOperatorNotAllowedWithColumn($leaf, $fieldSchema);
        self::throwIfValueNotAllowedWithOperator($leaf, $fieldSchema);
        self::throwIfOperatorNotAllowedWithColumnType($leaf, $fieldSchema);
        self::throwIfValueNotAllowedWithColumnType($leaf, $fieldSchema);
    }

    /**
     * @throws \Exception
     */
    private static function throwIfOperatorNotAllowedWithColumn(ConditionTreeLeaf $leaf, ColumnSchema|RelationSchema $columnSchema): void
    {
        $operators = $columnSchema->getFilterOperators();

        if (! isset($operators[$leaf->getOperator()])){
            throw new \Exception(
                'The given operator ' . $leaf->getOperator() .
                ' is not supported by the column: ' .  $leaf->getField() . '\\n' .
                count($operators) === 0 ? 'The column is not filterable.' : 'The allowed operators are: ' . implode(',', $operators)
            );
        }
    }

    /**
     * @param ConditionTreeLeaf           $leaf
     * @param ColumnSchema|RelationSchema $columnSchema
     * @return void
     * @throws \Exception
     */
    private static function throwIfValueNotAllowedWithOperator(ConditionTreeLeaf $leaf, ColumnSchema|RelationSchema $columnSchema): void
    {
        $value = $leaf->getValue();
        $valueType = TypeGetter::get($value, $columnSchema->getColumnType());
        $allowedTypes = Rules::getAllowedTypesForOperator($leaf->getOperator());

        if (!in_array($valueType, $allowedTypes, true)) {
            throw new \Exception(
                'The given value attribute ' . $value .
                ' has an unexpected value for the given operator ' . $leaf->getOperator() . '\\n' .
                count($allowedTypes) === 0 ? 'The value attribute must be empty.' : 'The allowed types of the field value are: ' . implode(',', $allowedTypes)
            );
        }
    }

    /**
     * @throws \Exception
     */
    private static function throwIfOperatorNotAllowedWithColumnType(ConditionTreeLeaf $leaf, ColumnSchema|RelationSchema $columnSchema): void
    {
        $allowedOperators = Rules::getAllowedOperatorsForColumnType($columnSchema->getColumnType());

        if(!in_array($leaf->getOperator(), $allowedOperators, true)) {
            throw new \Exception(
                'The given operator ' . $leaf->getOperator() .
                ' is not allowed with the columnType schema: ' . $columnSchema->getColumnType() . '\\n' .
                'The allowed types are: ' . implode(',', $allowedOperators)
            );
        }
    }

    private static function throwIfValueNotAllowedWithColumnType(ConditionTreeLeaf $leaf,  ColumnSchema|RelationSchema $columnSchema): void
    {
        $allowedTypes = Rules::getAllowedTypesForColumnType($columnSchema->getColumnType());

        FieldValidator::validateValue($leaf->getField(), $columnSchema, $leaf->getValue(), $allowedTypes);
    }
}
