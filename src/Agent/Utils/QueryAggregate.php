<?php

namespace ForestAdmin\AgentPHP\Agent\Utils;

use ForestAdmin\AgentPHP\Agent\Utils\Traits\FormatQuery;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Collection;

class QueryAggregate
{
    use FormatQuery;

    protected string $aggregate;

    protected string $field;

    public function __construct(
        protected CollectionContract $collection,
        protected Builder $query,
        protected Aggregation $aggregation,
        protected ?int $limit = null
    ) {
        $this->aggregate = strtolower($aggregation->getOperation());
        $this->field = $aggregation->getField() ? $this->formatField($this->collection, $aggregation->getField()) : '*';
    }

    public static function of(CollectionContract $collection, Builder $query, Aggregation $aggregation, ?int $limit = null): self
    {
        return (new static($collection, $query, $aggregation, $limit));
    }

    public function get(): array
    {
        foreach ($this->aggregation->getGroups() as $group) {
            $field = $this->formatField($this->collection, $group['field']);
            $this->query->addSelect($field)->groupBy($field);
        }

        $this->query->addSelect(self::raw("$this->aggregate($this->field) AS '$this->aggregate'"))
            ->orderBy($this->aggregate, 'DESC')
            ->limit($this->limit);

        return $this->computeResultAggregate($this->query->get());
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
