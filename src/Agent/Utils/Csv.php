<?php

namespace ForestAdmin\AgentPHP\Agent\Utils;

use League\Csv\CannotInsertRecord;
use League\Csv\Exception;
use League\Csv\Writer;

class Csv
{
    /**
     * @throws CannotInsertRecord
     * @throws Exception
     */
    public static function make(array $rows, array $header, string $filename): string
    {
        $csv = Writer::createFromString();
        $csv->insertOne($header);

        $records = [];
        foreach ($rows as $row) {
            $records[] = self::formatField($row);
        }
        $csv->insertAll($records);

        return $csv->toString();
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
