<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query;

use Closure;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Record;

class Aggregation
{
    // todo checks all methods public to private?

    public function __construct(protected string $operation, protected ?string $field = null, protected ?array $groups = [])
    {
        $this->validate($this->operation);
    }

    public function validate($operation)
    {
        if (! in_array($operation, ['Count', 'Sum', 'Avg', 'Max', 'Min'], true)) {
            throw new ForestException("Aggregate operation $operation not allowed");
        }
    }

    public function getOperation(): string
    {
        return $this->operation;
    }

    public function getField(): ?string
    {
        return $this->field;
    }

    public function getGroups(): ?array
    {
        return $this->groups;
    }

    public function getProjection()
    {
        $aggregateFields = [];
        if ($this->field) {
            $aggregateFields[] = $this->field;
        }

        if ($this->groups) {
            foreach ($this->groups as $group) {
                $aggregateFields[] = $group['field'];
            }
        }

        return new Projection($aggregateFields);
    }

    public function replaceFields(Closure $handler): self
    {
        $result = clone $this;

        if ($result->field) {
            $result->field = $handler($result->field);
        }

        $result->groups = collect($result->groups)->map(
            fn ($group) => [
                'field'     => $handler($group['field']),
                'operation' => $group['operation'] ?? null,
            ]
        )
            ->toArray();

        return $result;
    }

    public function override(...$args): self
    {
        return new self(...array_merge($this->toArray(), $args));
    }

    public function apply(array $records, string $timezone, ?int $limit = null): array
    {
        $rows = $this->formatSummaries($this->createSummaries($records, $timezone));

        collect($rows)->sort(function ($r1, $r2) {
            if ($r1['value'] === $r2['value']) {
                return 0;
            }

            return $r1['value'] < $r2['value'] ? 1 : -1;
        })->toArray();

        if ($limit && count($rows) > $limit) {
            $rows = array_slice($rows, 0, $limit);
        }

        return $rows;
    }

    public function nest(?string $prefix = null): self
    {
        if (null === $prefix) {
            return $this;
        }

        $nestedField = null;
        $nestedGroups = [];

        if ($this->field) {
            $nestedField = "$prefix:$this->field";
        }

        if (count($this->groups) > 0) {
            $nestedGroups = collect($this->groups)->map(fn ($item) => [
                'field'     => $prefix . ':' . $item['field'],
                'operation' => $item['operation'],
            ])->toArray();
        }

        return new self(operation: $this->operation, field: $nestedField, groups: $nestedGroups);
    }

    public function toArray(): array
    {
        return [
            'operation' => $this->operation,
            'field'     => $this->field,
            'groups'    => $this->groups,
        ];
    }

    private function createSummaries(array $records, string $timezone): array
    {
        $groupingMap = [];

        foreach ($records as $record) {
            $group = $this->createGroup($record, $timezone);
            $uniqueKey = sha1(serialize($group));
            $summary = $groupingMap[$uniqueKey] ?? $this->createSummary($group);

            $this->updateSummaryInPlace($summary, $record);

            $groupingMap[$uniqueKey] = $summary;
        }

        return array_values($groupingMap);
    }

    private function formatSummaries(array $summaries): array
    {
        return $this->operation === 'Avg'
            ? collect($summaries)
                ->filter(fn ($summary) => $summary['Count'] > 0)
                ->map(fn ($summary) => [
                    'group' => $summary['group'],
                    'value' => $summary['Sum'] / $summary['Count'],
                ])
                ->toArray()
            : collect($summaries)
                ->map(fn ($summary) => [
                    'group' => $summary['group'],
                    'value' => $this->operation === 'Count' && ! $this->field ? $summary['starCount'] : $summary[$this->operation],
                ])
                ->toArray();
    }

    private function createGroup(array $record, string $timezone): array
    {
        $group = [];
        foreach ($this->groups as $value) {
            $groupValue = Record::getFieldValue($record, $value['field']);
            $group[$value['field']] = $this->applyDateOperation($groupValue, $value['operation'] ?? null, $timezone);
        }

        return $group;
    }

    private function applyDateOperation(?string $value, ?string $operation, string $timezone): ?string
    {
        return match ($operation) {
            'Year'  => (new \DateTime($value, new \DateTimeZone($timezone)))->format('Y-01-01'),
            'Month' => (new \DateTime($value, new \DateTimeZone($timezone)))->format('Y-m-01'),
            'Day'   => (new \DateTime($value, new \DateTimeZone($timezone)))->format('Y-m-d'),
            'Week'  => (new \DateTime($value, new \DateTimeZone($timezone)))->modify('first day of this month')->format('Y-m-d'),
            default => $value,
        };
    }

    private function createSummary(array $group): array
    {
        return [
            'group'     => $group,
            'starCount' => 0,
            'Count'     => 0,
            'Sum'       => 0,
            'Min'       => null,
            'Max'       => null,
        ];
    }

    private function updateSummaryInPlace(array &$summary, array $record): void
    {
        $summary['starCount']++;

        if ($this->field) {
            $value = Record::getFieldValue($record, $this->field);

            if ($value !== null) {
                $min = $summary['Min'];
                $max = $summary['Max'];

                $summary['Count']++;
                if ($min === null || $value < $min) {
                    $summary['Min'] = $value;
                }
                if ($max === null || $value < $max) {
                    $summary['Max'] = $value;
                }
            }

            if (is_numeric($value)) {
                $summary['Sum'] += $value;
            }
        }
    }
}
