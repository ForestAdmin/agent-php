<?php

namespace ForestAdmin\AgentPHP\Agent\Utils\ForestSchema;

final class FrontendValidation
{
    public const OPERATOR_VALIDATION_TYPE_MAP = [
        'Present'     => 'is present',
        'GreaterThan' => 'is greater than',
        'LessThan'    => 'is less than',
        'LongerThan'  => 'is longer than',
        'ShorterThan' => 'is shorter than',
        'Contains'    => 'contains',
        'Like'        => 'is like',
    ];

    public static function convertValidationList(array $predicates = []): array
    {
        if (empty($predicates)) {
            return [];
        }

        $result = [];
        foreach ($predicates as $predicate) {
            if (is_array($predicate) && array_key_exists('operator', $predicate) && in_array($predicate['operator'], self::OPERATOR_VALIDATION_TYPE_MAP, true)) {
                $result[] = [
                    $predicate,
                    'value'   => $predicate['value'],
                    'message' => null,
                ];
            }
        }

        return $result;
    }
}
