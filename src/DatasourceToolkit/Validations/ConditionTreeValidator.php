<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Validations;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTree;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Schema\RelationSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Collection as CollectionUtils;

class ConditionTreeValidator
{
    /**
     * @throws ForestException
     */
    public static function validate(ConditionTree $conditionTree, CollectionContract $collection): void
    {
        if ($conditionTree instanceof ConditionTreeBranch) {
            self::validateBranch($conditionTree, $collection);
        } elseif ($conditionTree instanceof ConditionTreeLeaf) {
            self::validateLeaf($conditionTree, $collection);
        } else {
            throw new ForestException('Unexpected condition tree type');
        }
    }

    /**
     * @throws ForestException
     */
    private static function validateBranch(ConditionTreeBranch $branch, CollectionContract $collection): void
    {
        if (! in_array($branch->getAggregator(), ['And', 'Or'])) {
            throw new ForestException('The given aggregator ' . $branch->getAggregator() . ' is not supported. The supported values are: [\'Or\', \'And\']');
        }

        foreach ($branch->getConditions() as $condition) {
            self::validate($condition, $collection);
        }
    }

    /**
     * @throws ForestException
     */
    private static function validateLeaf(ConditionTreeLeaf $leaf, CollectionContract $collection): void
    {
        $fieldSchema = CollectionUtils::getFieldSchema($collection, $leaf->getField());

        self::throwIfOperatorNotAllowedWithColumn($leaf, $fieldSchema);
        self::throwIfValueNotAllowedWithOperator($leaf, $fieldSchema);
        self::throwIfOperatorNotAllowedWithColumnType($leaf, $fieldSchema);
        self::throwIfValueNotAllowedWithColumnType($leaf, $fieldSchema);
    }

    /**
     * @throws ForestException
     */
    private static function throwIfOperatorNotAllowedWithColumn(ConditionTreeLeaf $leaf, ColumnSchema|RelationSchema $columnSchema): void
    {
        $operators = $columnSchema->getFilterOperators();

        if (! in_array($leaf->getOperator(), $operators, true)) {
            throw new ForestException(
                'The given operator ' . $leaf->getOperator() .
                ' is not supported by the column: ' . $leaf->getField() . '. ' .
                count($operators) === 0 ? 'The column is not filterable.' : 'The allowed operators are: ' . implode(',', $operators)
            );
        }
    }

    /**
     * @param ConditionTreeLeaf           $leaf
     * @param ColumnSchema|RelationSchema $columnSchema
     * @return void
     * @throws ForestException
     */
    private static function throwIfValueNotAllowedWithOperator(ConditionTreeLeaf $leaf, ColumnSchema|RelationSchema $columnSchema): void
    {
        $value = $leaf->getValue();
        $valueType = TypeGetter::get($value, $columnSchema->getColumnType());
        $allowedTypes = Rules::getAllowedTypesForOperator($leaf->getOperator());


        if ($valueType instanceof ArrayType) {
            // todo refacto ValidationType
            $valueType = $valueType->label;
        }

        if (! in_array($valueType, $allowedTypes, true)) {
            throw new ForestException(
                'The given value attribute ' . (is_array($value) ? '[' . implode(',', $value) . ']' : $value) .
                ' has an unexpected value for the given operator ' . $leaf->getOperator() . '. ' .
                count($allowedTypes) === 0 ? 'The value attribute must be empty.' : 'The allowed types of the field value are: ' . implode(',', $allowedTypes)
            );
        }
    }

    /**
     * @throws ForestException
     */
    private static function throwIfOperatorNotAllowedWithColumnType(ConditionTreeLeaf $leaf, ColumnSchema|RelationSchema $columnSchema): void
    {
        $allowedOperators = Rules::getAllowedOperatorsForColumnType($columnSchema->getColumnType());

        if (! in_array($leaf->getOperator(), $allowedOperators, true)) {
            throw new ForestException(
                'The given operator ' . $leaf->getOperator() .
                ' is not allowed with the columnType schema: ' . $columnSchema->getColumnType() . '. ' .
                'The allowed types are: ' . implode(',', $allowedOperators)
            );
        }
    }

    private static function throwIfValueNotAllowedWithColumnType(ConditionTreeLeaf $leaf, ColumnSchema|RelationSchema $columnSchema): void
    {
        $allowedTypes = Rules::getAllowedTypesForColumnType($columnSchema->getColumnType());

        FieldValidator::validateValue($leaf->getField(), $columnSchema, $leaf->getValue(), $allowedTypes);
    }
}
