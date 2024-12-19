<?php

namespace ForestAdmin\AgentPHP\Agent\Utils;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTree;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;

class ContextVariablesInjector
{
    public static function injectContextInFilter(?ConditionTree $filter, ContextVariables $contextVariables)
    {
        if (! $filter) {
            return null;
        }

        if ($filter instanceof ConditionTreeBranch) {
            return $filter->replaceLeafs(fn ($condition) => self::injectContextInFilter($condition, $contextVariables));
        }

        return $filter->replaceLeafs(fn ($leaf) => $leaf->override(value: self::injectContextInValue($filter->getValue(), $contextVariables)));
    }

    public static function injectContextInValue($value, ContextVariables $contextVariables)
    {
        if (! is_string($value)) {
            return $value;
        }

        return preg_replace_callback(
            '/{{([^}]+)}}/',
            fn ($match) => $contextVariables->getValue($match[1]),
            $value
        );
    }

    public static function injectContextInNativeQuery(string $query, ContextVariables $contextVariables): array
    {
        if (! is_string($query)) {
            return $query;
        }

        $queryWithContextVariablesInjected = $query;
        $encounteredVariables = [];

        while(preg_match('/{{([^}]+)}}/', $queryWithContextVariablesInjected, $match)) {
            $contextVariableKey = $match[1];

            if (in_array($contextVariableKey, $encounteredVariables, true)) {
                continue;
            }

            $queryWithContextVariablesInjected = preg_replace(
                '/{{' . $contextVariableKey . '}}/',
                '?',
                $queryWithContextVariablesInjected
            );

            $encounteredVariables[] = $contextVariables->getValue($contextVariableKey);
        }

        return [$queryWithContextVariablesInjected, $encounteredVariables];
    }
}
