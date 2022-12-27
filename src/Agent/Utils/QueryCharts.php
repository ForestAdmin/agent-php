<?php

namespace ForestAdmin\AgentPHP\Agent\Utils;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as IlluminateCollection;
use Illuminate\Support\Str;

class QueryCharts
{
    protected string $aggregate;

    protected string $field;

    public function __construct(
        protected CollectionContract $collection,
        protected Builder $query,
        protected Aggregation $aggregation,
        protected ?int $limit = null
    ) {
        $this->aggregate = strtolower($aggregation->getOperation());
        $this->field = Str::contains($aggregation->getField(), ':')
            ? Str::replace(':', '.', $aggregation->getField())
            : $this->collection->getTableName() . ($aggregation->getField() ? '.' . $aggregation->getField() : null);
    }

    public static function of(CollectionContract $collection, Builder $query, Aggregation $aggregation, ?int $limit = null): self
    {
        return (new static($collection, $query, $aggregation, $limit));
    }

    public function queryValue(): IlluminateCollection
    {
        return $this->query
            ->addSelect(self::raw("$this->aggregate($this->field) AS $this->aggregate"))
            ->get();
    }

    public function queryObjective(): IlluminateCollection
    {
        return $this->query
            ->addSelect(self::raw("$this->aggregate($this->field) AS $this->aggregate"))
            ->get();
    }

    public function queryPie(): IlluminateCollection
    {
        $groupField = $this->formatField($this->aggregation->getGroups()[0]['field']);

        return $this->query->addSelect(self::raw("$groupField, $this->aggregate($this->field) AS $this->aggregate"))
            ->groupBy($groupField)
            ->get();
    }

    public function queryLine(): IlluminateCollection
    {
        $groupField = $this->formatField($this->aggregation->getGroups()[0]['field']);

        $results = $this->query
            ->addSelect(self::raw("$groupField, $this->aggregate($this->field) AS $this->aggregate"))
            ->groupBy($groupField)
            ->get();

        $values = collect();
        foreach ($results as $result) {
            $key = Carbon::parse(Arr::get((array) $result, $this->aggregation->getGroups()[0]['field']))
                ->format($this->getFormat());

            isset($values[$key]) ? $values[$key] += $result->{$this->aggregate} : $values[$key] = $result->{$this->aggregate};
        }

        return $values;
    }

    public function queryLeaderboard(): IlluminateCollection
    {
        $groupField = $this->formatField($this->aggregation->getGroups()[0]['field']);

        return $this->query->addSelect(self::raw("$groupField, $this->aggregate($this->field) AS $this->aggregate"))
            ->orderBy($this->aggregate, 'DESC')
            ->groupBy($groupField)
            ->limit($this->limit)
            ->get();
    }

    public function formatField(string $originalField): string
    {
        return Str::contains($originalField, ':')
            ? Str::replace(':', '.', $originalField)
            : $this->collection->getTableName() . '.' . $originalField;
    }

    private static function raw(string $value): Expression
    {
        return new Expression($value);
    }

    /**
     * @return string
     */
    private function getFormat(): string
    {
        if (! isset($this->aggregation->getGroups()[0]['operation'])) {
            throw new ForestException("The parameter time_range is not defined");
        }

        switch (Str::lower($this->aggregation->getGroups()[0]['operation'])) {
            case 'day':
                $format = 'd/m/Y';

                break;
            case 'week':
                $format = '\WW-Y';

                break;
            case 'month':
                $format = 'M Y';

                break;
            case 'year':
                $format = 'Y';

                break;
            default:
                $format = '';

                break;
        }

        return $format;
    }
}
