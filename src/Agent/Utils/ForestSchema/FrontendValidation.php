<?php

namespace ForestAdmin\AgentPHP\Agent\Utils\ForestSchema;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;

final class FrontendValidation
{
    public const OPERATOR_VALIDATION_TYPE_MAP = [
        Operators::PRESENT      => 'is present',
        Operators::GREATER_THAN => 'is greater than',
        Operators::LESS_THAN    => 'is less than',
        Operators::LONGER_THAN  => 'is longer than',
        Operators::SHORTER_THAN => 'is shorter than',
        Operators::CONTAINS     => 'contains',
        Operators::LIKE         => 'is like',
    ];

    public static function convertValidationList(array $predicates = []): array
    {
        if (empty($predicates)) {
            return [];
        }

        $result = [];
        foreach ($predicates as $predicate) {
            if (is_array($predicate)
                && array_key_exists('operator', $predicate)
                && array_key_exists($predicate['operator'], self::OPERATOR_VALIDATION_TYPE_MAP)
                && $type = self::OPERATOR_VALIDATION_TYPE_MAP[$predicate['operator']]
            ) {
                $errorValue = $predicate['value'] ? '(' . $predicate['value'] . ')' : '';
                $result[] = [
                    'type'    => $type,
                    'value'   => $predicate['value'],
                    'message' => 'Failed validation rule: ' . $predicate['operator'] . $errorValue,
                ];
            }
        }

        return $result;
    }
}
