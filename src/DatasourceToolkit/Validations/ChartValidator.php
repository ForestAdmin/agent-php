<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Validations;

use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

class ChartValidator
{
    public static function validate(bool $condition, $result, string $keyNames): bool
    {
        if (is_array($result)) {
            $result = collect($result);
        }

        if ($condition) {
            $resultKeys = $result->keys()->implode(',');
            throw new ForestException("The result columns must be named '$keyNames' instead of '$resultKeys'");
        }

        return true;
    }
}
