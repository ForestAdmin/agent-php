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


//        dd($value, $contextVariables);
//        return self::injectContextInValueCustom($value, fn ($contextVariableKey) => $contextVariables->getValue($contextVariableKey));
//        return self::injectContextInValueCustom($value, function ($contextVariableKey) use ($contextVariables) {
//            return $contextVariables->getValue($contextVariableKey);
//        });
    }

//    public static function injectContextInValueCustom($value, \Closure $replaceFunction)
//    {
//        if (! is_string($value)) {
//            return $value;
//        }
//
//        $value = 'toto {{currentUser.id}} test {{currentUser.name}}';
//
//        $valueWithContextVariablesInjected = $value;
//        $regex = '/{{([^}]+)}}/';
//        $encounteredVariables = [];
//
//        preg_match_all($regex, $value, $matches, PREG_OFFSET_CAPTURE);
//
//        foreach ($matches[1] as $match) {
//            $contextVariableKey = $match[0];
//
//            if (! in_array($contextVariableKey, $encounteredVariables, true)) {
//                //=/dd($contextVariableKey, $valueWithContextVariablesInjected);
//                $valueWithContextVariablesInjected = preg_replace_callback(
//                    '#{{currentUser.id}}#',
//                    $replaceFunction,
//                    $valueWithContextVariablesInjected
//                );
//            }
//            $encounteredVariables[] = $contextVariableKey;
//        }
//
//        return $valueWithContextVariablesInjected;



//        const encounteredVariables = [];
//            $a = 'currentUser.id';
//            $valueWithContextVariablesInjected = preg_replace_callback(
//                '{{currentUser.id}}',
//                $replaceFunction,
//                '{{currentUser.id}} test'
//        );



//        $valueWithContextVariablesInjected = $value;
//
//
//        while (preg_match($regex, $value, $matches, PREG_OFFSET_CAPTURE)) {
//            $contextVariableKey = $matches[1][0];
//
////            dd($contextVariableKey);
//            if (! in_array($contextVariableKey, $encounteredVariables, true)) {
//                //dd($contextVariableKey, $valueWithContextVariablesInjected);
//
//                $valueWithContextVariablesInjected = preg_replace_callback(
//                    '\{\{' . $contextVariableKey . '\}\}',
//                    $replaceFunction($contextVariableKey),
//                    $valueWithContextVariablesInjected
//                );
//            }
////            dd($valueWithContextVariablesInjected);
////
//            $encounteredVariables[] = $contextVariableKey;
//        }
//
//        return $valueWithContextVariablesInjected;
    //}

    //public static injectContextInValueCustom<ValueType>(
    //    value: ValueType,
    //    replaceFunction: (contextVariableName: string) => string,
    //  ) {
    //    if (typeof value !== 'string') {
    //      return value;
    //    }
    //
    //    let valueWithContextVariablesInjected: string = value;
    //    const regex = /{{([^}]+)}}/g;
    //    let match = regex.exec(value);
    //    const encounteredVariables = [];
    //
    //    while (match) {
    //      const contextVariableKey = match[1];
    //
    //      if (!encounteredVariables.includes(contextVariableKey)) {
    //        valueWithContextVariablesInjected = valueWithContextVariablesInjected.replace(
    //          new RegExp(`{{${contextVariableKey}}}`, 'g'),
    //          replaceFunction(contextVariableKey),
    //        );
    //      }
    //
    //      encounteredVariables.push(contextVariableKey);
    //      match = regex.exec(value);
    //    }
    //
    //    return valueWithContextVariablesInjected as unknown as ValueType;
    //  }
}
