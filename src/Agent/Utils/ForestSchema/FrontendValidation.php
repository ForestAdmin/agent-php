<?php

namespace ForestAdmin\AgentPHP\Agent\Utils\ForestSchema;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeEquivalent;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;

final class FrontendValidation
{
    /**
     * Convert a list of our validation rules to what we'll be sending to the frontend
     * @param ColumnSchema $column
     * @return array
     */
    public static function convertValidationList(ColumnSchema $column): array
    {
        if (! $column->getValidation()) {
            return [];
        }

        $rules = collect($column->getValidation())->map(fn ($rule) => self::simplifyRule($column->getColumnType(), $rule))
            ->toArray();
        self::removeDuplicatesInPlace($rules);

        return collect($rules)->map(fn ($rule) => self::supported()[$rule['operator']]($rule))->toArray();
    }

    private static function excluded(): array
    {
        return [
            Operators::FUTURE, Operators::PAST, Operators::TODAY, Operators::YESTERDAY,
            Operators::PREVIOUS_MONTH, Operators::PREVIOUS_QUARTER, Operators::PREVIOUS_WEEK, Operators::PREVIOUS_X_DAYS, Operators::PREVIOUS_YEAR,
            Operators::AFTER_X_HOURS_AGO, Operators::BEFORE_X_HOURS_AGO, Operators::PREVIOUS_X_DAYS_TO_DATE,
            Operators::PREVIOUS_MONTH_TO_DATE, Operators::PREVIOUS_QUARTER_TO_DATE, Operators::PREVIOUS_WEEK_TO_DATE, Operators::PREVIOUS_YEAR_TO_DATE,
        ];
    }

    /**
     * This is the list of operators which are supported in the frontend implementation of the validation rules
     * @return array
     */
    private static function supported(): array
    {
        return [
            Operators::PRESENT      => fn () => ['type' => 'is present', 'message' => 'Field is required'],
            Operators::AFTER        => fn ($rule) => [
                'type'    => 'is after',
                'value'   => $rule['value'],
                'message' => 'Value must be after ' . $rule['value'],
            ],
            Operators::BEFORE       => fn ($rule) => [
                'type'    => 'is before',
                'value'   => $rule['value'],
                'message' => 'Value must be before ' . $rule['value'],
            ],
            Operators::CONTAINS     => fn ($rule) => [
                'type'    => 'contains',
                'value'   => $rule['value'],
                'message' => 'Value must contain \'' . $rule['value'] . '\'',
            ],
            Operators::GREATER_THAN => fn ($rule) => [
                'type'    => 'is greater than',
                'value'   => $rule['value'],
                'message' => 'Value must be greater than ' . $rule['value'],
            ],
            Operators::LESS_THAN    => fn ($rule) => [
                'type'    => 'is less than',
                'value'   => $rule['value'],
                'message' => 'Value must be lower than ' . $rule['value'],
            ],
            Operators::LONGER_THAN  => fn ($rule) => [
                'type'    => 'is longer than',
                'value'   => $rule['value'],
                'message' => 'Value must be longer than ' . $rule['value'] . ' characters',
            ],
            Operators::SHORTER_THAN => fn ($rule) => [
                'type'    => 'is shorter than',
                'value'   => $rule['value'],
                'message' => 'Value must be shorter than ' . $rule['value'] . ' characters',
            ],
            Operators::MATCH        => fn ($rule) => [
                'type'    => 'is like', // `is like` actually expects a regular expression, not a 'like pattern'
                'value'   => $rule['value'],
                'message' => 'Value must match ' . $rule['value'],
            ],
        ];
    }

    /**
     * Convert one of our validation rules to a given number of frontend validation rules
     * @param array|string $columnType
     * @param array $rule
     * @return array|array[]
     */
    private static function simplifyRule(array|string $columnType, array $rule): array
    {
        // Operators which we don't want to end up the schema
        if (in_array($rule['operator'], self::excluded(), true)) {
            return [];
        }

        // Operators which are natively supported by the frontend
        if (isset(self::supported()[$rule['operator']])) {
            return $rule;
        }

        try {
            // Add the 'Equal|NotEqual' operators to unlock the `In|NotIn -> Match` replacement rules.
            // This is a bit hacky, but it allows to reuse the existing logic.
            $operators = array_keys(self::supported());
            $operators[] = 'Equal';
            $operators[] = 'NotEqual';

            // Rewrite the rule to use only operators that the frontend supports.
            $leaf = new ConditionTreeLeaf('field', $rule['operator'], $rule['value']);
            $timezone = 'Europe/Paris'; // we're sending the schema => use random tz
            $tree = ConditionTreeEquivalent::getEquivalentTree($leaf, $operators, $columnType, $timezone);

            $conditions = [];

            if ($tree instanceof ConditionTreeLeaf) {
                $conditions = [$tree];
            } elseif ($tree instanceof ConditionTreeBranch /*&& $tree->getAggregator() === 'And'*/) {
                $conditions = $tree->getConditions();
            }

            return collect($conditions)
                ->filter(fn ($c) => $c instanceof ConditionTreeLeaf)
                ->filter(fn ($c) => $c->getOperator() !== 'Equal' || $c->getOperator() !== 'NotEqual')
                ->flatMap(fn ($c) => self::simplifyRule($columnType, $c->toArray()))
                ->toArray();
        } catch (\Exception $e) {
            // Just ignore errors, they mean that the operator is not supported by the frontend
            // and that we don't have an automatic conversion for it.
            //
            // In that case we fallback to just validating the data entry in the agent (which is better
            // than nothing but will not be as user friendly as the frontend validation).
        }

        // Drop the rule if we don't know how to convert it (we could log a warning here).
        return [];
    }

    /**
     * The frontend crashes when it receives multiple rules of the same type.
     * This method merges the rules which can be merged and drops the others.
     * @param array $rules
     * @return void
     */
    private static function removeDuplicatesInPlace(array $rules): void
    {
        $used = [];

        foreach ($rules as $key => $value) {
            if (isset($used[$value['operator']])) {
                $rule = $rules[$used[$value['operator']]];
                $newRule = $value;
                unset($rules[$key]);

                self::mergeInto($rule, $newRule);
            } else {
                $used[$value['operator']] = $key;
            }
        }
    }

    private static function mergeInto(array &$validation, array $newRule): void
    {
        if ($validation['operator'] === Operators::GREATER_THAN || $validation['operator'] === Operators::AFTER || $validation['operator'] === Operators::LONGER_THAN) {
            $validation['value'] = max($validation['value'], $newRule['value']);
        } elseif ($validation['operator'] === Operators::LESS_THAN || $validation['operator'] === Operators::BEFORE || $validation['operator'] === Operators::SHORTER_THAN) {
            $validation['value'] = min($validation['value'], $newRule['value']);
        } elseif ($validation['operator'] === Operators::MATCH) {
            $validation['value'] = '/^(?=' . $validation['value'] . ')(?=' . $newRule['value'] . ').*$/';
        } else {
            // Ignore the rules that we can't deduplicate (we could log a warning here).
        }
    }
}
