<?php

namespace ForestAdmin\AgentPHP\BaseDatasource\Utils;

use ForestAdmin\AgentPHP\BaseDatasource\Contracts\BaseCollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Collection;

class QueryAggregate extends QueryConverter
{
    protected string $aggregate;

    protected string $field;

    public function __construct(
        protected BaseCollectionContract $collection,
        protected string $timezone,
        protected Aggregation $aggregation,
        protected ?Filter $filter = null,
        protected ?int $limit = null
    ) {
        parent::__construct($collection, $timezone, $filter, $this->aggregation->getProjection());
        $this->aggregate = strtolower($aggregation->getOperation());
        $this->field = $aggregation->getField() ? $this->formatField($aggregation->getField()) : '*';
    }

    public function get(): array
    {
        foreach ($this->aggregation->getGroups() as $group) {
            $field = $this->formatField($group['field']);
            $this->query->addSelect($field)->groupBy($field);
        }

        $this->query->addSelect(self::raw("$this->aggregate($this->field) AS $this->aggregate"))
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
