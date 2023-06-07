<?php

namespace ForestAdmin\AgentPHP\Agent\Utils;

class ArrayHelper
{
    public static function ksortRecursive(&$array): bool
    {
        // exit recursive loop
        if (! is_array($array)) {
            return false;
        }

        ksort($array);
        foreach ($array as &$arr) {
            self::ksortRecursive($arr);
        }

        return true;
    }
}
