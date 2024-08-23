<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Computed\Utils;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;

class Flattener
{
    public const MARKER_NAME = '__null_marker';

    public static function withNullMarker(Projection $projection): Projection
    {
        $newProjection = new Projection($projection->toArray());

        foreach ($projection as $path) {
            $parts = explode(':', $path);
            $partsCount = count($parts);

            for ($i = 1; $i < $partsCount; $i++) {
                $newProjection->push(implode(':', array_slice($parts, 0, $i)) . ':' . self::MARKER_NAME);
            }
        }

        return $newProjection->unique();
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

        foreach ($projection as $index => $path) {
            $parts = array_filter(explode(':', $path), function ($part) {
                return ! in_array($part, [self::MARKER_NAME, '*']);
            });

            foreach ($flatten[$index] as $recordIndex => $value) {
                if ($value instanceof Undefined) {
                    continue;
                }

                $record = &$records[$recordIndex];
                foreach ($parts as $partIndex => $part) {
                    if ($partIndex === count($parts) - 1) {
                        $record[$part] = $value;
                    } else {
                        if (! isset($record[$part]) || ! is_array($record[$part])) {
                            $record[$part] = [];
                        }
                        $record = &$record[$part];
                    }
                }
            }
        }

        return $records;
    }
}
