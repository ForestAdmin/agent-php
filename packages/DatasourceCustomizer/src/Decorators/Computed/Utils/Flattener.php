<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Computed\Utils;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;

class Flattener
{
    public const MARKER_NAME = '__null_marker';

    public static function withNullMarker(Projection $projection): Projection
    {
        $paths = $projection->toArray();

        foreach ($paths as $path) {
            $parts = explode(':', $path);
            $partsCount = count($parts);

            for ($i = 1; $i < $partsCount; $i++) {
                $paths[] = implode(':', array_slice($parts, 0, $i)) . ':' . self::MARKER_NAME;
            }
        }

        $unique = collect($paths)->unique();

        return new Projection($unique->values()->all());
    }

    public static function flatten(array $records, Projection $projection): array
    {
        return $projection->map(function ($field) use ($records) {
            $parts = array_filter(explode(':', $field), function ($part) {
                return $part !== '*';
            });

            return array_map(function ($record) use ($parts) {
                $value = $record;

                foreach (array_slice($parts, 0, count($parts) - 1) as $part) {
                    $value = is_array($value) && array_key_exists($part, $value) ? $value[$part] : new Undefined();
                }

                $lastPart = end($parts);
                if ($lastPart === self::MARKER_NAME) {
                    return $value === null ? null : new Undefined();
                } elseif (is_array($value) && array_key_exists($lastPart, $value)) {
                    return $value[$lastPart];
                }

                return new Undefined();
            }, $records);
        })->toArray();
    }

    public static function unFlatten(array $flatten, Projection $projection): array
    {
        $numRecords = count($flatten[0] ?? []);
        $records = array_fill(0, $numRecords, []);

        for ($recordIndex = 0; $recordIndex < $numRecords; $recordIndex++) {
            $records[$recordIndex] = [];

            foreach ($projection as $pathIndex => $path) {
                $parts = array_filter(explode(':', $path), function ($part) {
                    return ! in_array($part, [self::MARKER_NAME, '*']);
                });
                $value = $flatten[$pathIndex][$recordIndex];

                // Ignore undefined values.
                if ($value instanceof Undefined) {
                    continue;
                }

                // Set all others (including null)
                $record = &$records[$recordIndex];

                foreach ($parts as $partIndex => $part) {
                    if ($partIndex === count($parts) - 1) {
                        $record[$part] = $value;
                    } elseif (! array_key_exists($part, $record)) {
                        $record[$part] = [];
                    }

                    $record = &$record[$part];
                }
            }
        }

        return $records;
    }
}
