<?php

namespace ForestAdmin\AgentPHP\Agent\Utils;

use League\Csv\Writer;

class Csv
{
    public static function make(array $rows, array $header, string $filename): void
    {
        $csv = Writer::createFromString();
        $csv->insertOne($header);
        foreach ($rows as $row) {
            $csv->insertOne(self::formatField($row));
        }

        $csv->toString();

        $csv->output($filename);
    }

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
