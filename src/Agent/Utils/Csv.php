<?php

namespace ForestAdmin\AgentPHP\Agent\Utils;

class Csv
{
    public static function formatField(array $field): array
    {
        foreach ($field as $key => $value) {
            if (is_bool($value)) {
                $field[$key] = (int) $value;
            }
            if (is_array($value)) {
                $field[$key] = '';
            }
            if ($value instanceof \DateTime) {
                $field[$key] = $value->format('Y-m-d h:i:s');
            }
            if ($value instanceof \Date) {
                $field[$key] = $value->format('Y-m-d');
            }
        }

        return array_values($field);
    }
}
