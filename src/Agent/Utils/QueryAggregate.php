<?php

namespace ForestAdmin\AgentPHP\Agent\Utils;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class QueryAggregate
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

    public function get(): array
    {
        foreach ($this->aggregation->getGroups() as $group) {
            $field = Str::contains($group['field'], ':')
                ? Str::replace(':', '.', $group['field'])
                : $this->collection->getTableName() . '.' . $group['field'];
            $this->query->addSelect($field)->groupBy($field);
        }

        $field = $this->aggregation->getField() ?? '*';
        $this->query->addSelect(self::raw("$this->aggregate($field) AS $this->aggregate"))
            ->orderBy($this->aggregate, 'DESC')
            ->limit($this->limit);


        return $this->computeResultAggregate($this->query->get());
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

    private function computeResultAggregate(Collection $rows): array
    {
        return $rows->map(
            fn ($row) => [
                'value' => $row->{$this->aggregate},
                'group' => collect($this->aggregation->getGroups() ?? [])
                    ->reduce(
                        function ($memo, $group) use ($row) {
                            $memo[$group['field']] = $row->{$group['field']};

                            return $memo;
                        },
                        []
                    ),
            ]
        )->toArray();
    }
}
