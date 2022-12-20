<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query;

use Closure;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Record as RecordUtils;
use Illuminate\Support\Collection as IlluminateCollection;
use Illuminate\Support\Str;

class Sort extends IlluminateCollection
{
    public function __construct($items = [])
    {
        foreach ($items as $item) {
            if (! isset($item['field'], $item['ascending'])
                || ! is_string($item['field'])
                || ! is_bool($item['ascending'])
            ) {
                throw new ForestException('Invalid sort clause, key "field" and "ascending" must be present and should be of type string and boolean.');
            }
        }

        parent::__construct($items);
    }

    public function getProjection(): Projection
    {
        return new Projection($this->collect()->map(fn ($clause) => $clause['field'])->toArray());
    }

    public function replaceClauses(Closure $closure): Sort
    {
        return $this->collect()
            ->map(fn ($clause) => $closure($clause))
            ->reduce(
                function ($memo, $closureResult) {
                    return new Sort([...$memo, ...$closureResult]);
                },
                collect()
            );
    }

    public function nest(string $prefix): Sort
    {
        return $prefix !== '' ? $this->map(fn ($clause) => ['field' => $prefix . ':' . $clause['field'], 'ascending' => $clause['ascending']]) : $this;
    }

    public function inverse(): Sort
    {
        return $this->map(fn ($clause) => ['field' => $clause['field'], 'ascending' => ! $clause['ascending']]);
    }

    public function unnest(): Sort
    {
        $prefix = Str::before($this->first()['field'], ':');

        if (! $this->every(fn ($clause) => Str::startsWith($clause['field'], $prefix))) {
            throw new ForestException('Cannot unnest sort.');
        }

        return $this->map(fn ($clause) => ['field' => Str::after($clause['field'], ':'), 'ascending' => $clause['ascending']]);
    }

    public function apply(array $records): array
    {
        $length = $this->count();

        return collect($records)->sort(
            function ($a, $b) use ($length) {
                for ($i = 0; $i < $length; $i++) {
                    $clause = $this->get($i);
                    $valueOnA = RecordUtils::getFieldValue($a, $clause['field']);
                    $valueOnB = RecordUtils::getFieldValue($b, $clause['field']);

                    if ($valueOnA < $valueOnB) {
                        return $clause['ascending'] ? -1 : 1;
                    }

                    if ($valueOnA > $valueOnB) {
                        return $clause['ascending'] ? 1 : -1;
                    }
                }

                return 0;
            }
        )->toArray();
    }
}
