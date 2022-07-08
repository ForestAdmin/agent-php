<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Validations;


use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTree;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\RelationSchema;

class ConditionTreeValidator
{
    public static function validate(ConditionTree $conditionTree, Collection $collection): void
    {
        if ($conditionTree instanceof ConditionTreeBranch) {
//            ConditionTreeValidator.validateBranch(conditionTree, collection);
        } else {
//            ConditionTreeValidator.validateLeaf(conditionTree, collection);
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

    private static function validateLeafs(ConditionTreeLeaf $leaf, Collection $collection): void
    {

    }

    private static function throwIfOperatorNotAllowedWithColumn(ConditionTreeLeaf $leaf, $columnSchema): void
    {

    }

    /**
     * @param ConditionTreeLeaf           $leaf
     * @param ColumnSchema|RelationSchema $columnSchema
     * @return void
     */
    private static function throwIfValueNotAllowedWithOperator(ConditionTreeLeaf $leaf, ColumnSchema|RelationSchema $columnSchema): void
    {
        $value = $leaf->getValue();
        $valueType = TypeGetter::get($value, $columnSchema->getColumnType());

        /*

    const allowedTypes = MAP_ALLOWED_TYPES_FOR_OPERATOR[conditionTree.operator];

    if (!allowedTypes.includes(valueType)) {
      throw new ValidationError(
        `The given value attribute '${JSON.stringify(
          value,
        )} (type: ${valueType})' has an unexpected value ` +
          `for the given operator '${conditionTree.operator}'.\n` +
          `${
            allowedTypes.length === 0
              ? 'The value attribute must be empty.'
              : `The allowed types of the field value are: [${allowedTypes}].`
          }`,
      );
    }
  }
         */

    }

    /*

  private static validateLeaf(leaf: ConditionTreeLeaf, collection: Collection): void {
    const fieldSchema = CollectionUtils.getFieldSchema(collection, leaf.field) as ColumnSchema;

    ConditionTreeValidator.throwIfOperatorNotAllowedWithColumn(leaf, fieldSchema);
    ConditionTreeValidator.throwIfValueNotAllowedWithOperator(leaf, fieldSchema);
    ConditionTreeValidator.throwIfOperatorNotAllowedWithColumnType(leaf, fieldSchema);
    ConditionTreeValidator.throwIfValueNotAllowedWithColumnType(leaf, fieldSchema);
  }

  private static throwIfOperatorNotAllowedWithColumn(
    conditionTree: ConditionTreeLeaf,
    columnSchema: ColumnSchema,
  ): void {
    const operators = columnSchema.filterOperators;

    if (!operators?.has(conditionTree.operator)) {
      throw new ValidationError(
        `The given operator '${conditionTree.operator}' ` +
          `is not supported by the column: '${conditionTree.field}'.\n${
            operators?.size
              ? `The allowed types are: [${[...operators]}]`
              : 'the column is not filterable'
          }`,
      );
    }
  }


  private static throwIfOperatorNotAllowedWithColumnType(
    conditionTree: ConditionTreeLeaf,
    columnSchema: ColumnSchema,
  ): void {
    const allowedOperators =
      MAP_ALLOWED_OPERATORS_FOR_COLUMN_TYPE[columnSchema.columnType as PrimitiveTypes];

    if (!allowedOperators.includes(conditionTree.operator)) {
      throw new ValidationError(
        `The given operator '${conditionTree.operator}' ` +
          `is not allowed with the columnType schema: '${columnSchema.columnType}'.\n` +
          `The allowed types are: [${allowedOperators}]`,
      );
    }
  }

  private static throwIfValueNotAllowedWithColumnType(
    conditionTree: ConditionTreeLeaf,
    columnSchema: ColumnSchema,
  ): void {
    const { value, field } = conditionTree;
    const { columnType } = columnSchema;
    const allowedTypes = MAP_ALLOWED_TYPES_FOR_COLUMN_TYPE[columnType as PrimitiveTypes];

    FieldValidator.validateValue(field, columnSchema, value, allowedTypes);
  }
     */
}
