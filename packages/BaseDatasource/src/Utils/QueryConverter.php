<?php

namespace ForestAdmin\AgentPHP\BaseDatasource\Utils;

use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\FrontendFilterable;
use ForestAdmin\AgentPHP\BaseDatasource\Contracts\BaseCollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTree;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Page;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Sort;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\RelationSchema;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class QueryConverter extends QueryBuilder
{
    protected array $basicSymbols = [
        Operators::EQUAL        => '=',
        Operators::NOT_EQUAL    => '!=',
        Operators::GREATER_THAN => '>',
        Operators::LESS_THAN    => '<',
    ];

    public function __construct(
        BaseCollectionContract $collection,
        protected string         $timezone,
        protected ?Filter        $filter = null,
        protected ?Projection    $projection = null,
    ) {
        parent::__construct($collection);
        $this->build();
    }

    private function build(): void
    {
        $this->applyProjection();

        if ($this->filter) {
            $this->applySort();

            $this->applyPagination();

            $this->applyConditionTree();
        }
    }

    private function applyProjection(): void
    {
        if ($this->projection && $this->projection->isNotEmpty()) {
            $selectRaw = collect($this->projection->columns())
                ->map(fn ($field) => "$this->tableName.$field")
                ->toArray();
            foreach ($this->projection->relations() as $relation => $relationFields) {
                /** @var RelationSchema $relation */
                $relationSchema = $this->collection->getFields()[$relation];
                $relationTableName = $this->collection->getDataSource()->getCollection($relationSchema->getForeignCollection())->getTableName();
                $this->addJoinRelation($relationSchema, $relationTableName, $relation);
                $relationFields->map(function ($field) use (&$selectRaw, $relation) {
                    $selectRaw[] = "$relation.$field as $relation.$field";
                });
            }

            $this->query->select($selectRaw);
        }
    }

    private function applySort(): void
    {
        /** @var Sort $sort */
        if (method_exists($this->filter, 'getSort') && $sort = $this->filter->getSort()) {
            foreach ($sort as $value) {
                if (! Str::contains($value['field'], ':')) {
                    $this->query->orderBy(
                        $this->tableName . '.' . $value['field'],
                        $value['ascending'] ? 'ASC' : 'DESC'
                    );
                } else {
                    $this->query->orderBy(
                        Str::before($value['field'], ':') . '.' . Str::after($value['field'], ':'),
                        $value['ascending'] ? 'ASC' : 'DESC'
                    );
                }
            }
        }
    }

    private function applyPagination(): void
    {
        /** @var Page $page */
        if (method_exists($this->filter, 'getPage') && $page = $this->filter->getPage()) {
            $this->query->offset($page->getOffset())->limit($page->getLimit());
        }
    }

    private function applyConditionTree(): void
    {
        if ($conditionTree = $this->filter->getConditionTree()) {
            $this->query->where($this->convertConditionTree($conditionTree, $this->query));
        }
    }

    private function convertConditionTree(ConditionTree $conditionTree, Builder $query, ?string $aggregator = null): void
    {
        if ($conditionTree instanceof ConditionTreeBranch) {
            $query->where(
                function ($q) use ($conditionTree) {
                    foreach ($conditionTree->getConditions() as $condition) {
                        $this->convertConditionTree($condition, $q, Str::lower($conditionTree->getAggregator()));
                    }
                },
                null,
                null,
                $aggregator ?? Str::lower($conditionTree->getAggregator())
            );
        } elseif ($conditionTree instanceof ConditionTreeLeaf
            && in_array($conditionTree->getOperator(), FrontendFilterable::BASE_DATEONLY_OPERATORS, true)
        ) {
            $this->computeDateOperator($conditionTree, $query, $aggregator ?? 'and');
        } else {
            $this->computeMainOperator($conditionTree, $query, $aggregator ?? 'and');
        }
    }

    private function computeDateOperator(ConditionTreeLeaf $conditionTreeLeaf, Builder $query, string $aggregator): void
    {
        $field = $this->formatField($conditionTreeLeaf->getField());
        $value = $conditionTreeLeaf->getValue();
        $operator = $conditionTreeLeaf->getOperator();

        switch ($operator) {
            case Operators::TODAY:
                $query->whereBetween(
                    $field,
                    [
                        Carbon::now($this->timezone)->startOfDay(),
                        Carbon::now($this->timezone)->endOfDay(),
                    ],
                    $aggregator
                );

                break;
            case Operators::BEFORE:
                $query->where($field, '<', new Carbon(new \DateTime($value), $this->timezone), $aggregator);

                break;
            case Operators::AFTER:
                $query->where($field, '>', new Carbon(new \DateTime($value), $this->timezone), $aggregator);

                break;
            case Operators::PREVIOUS_X_DAYS:
                $query->whereBetween(
                    $field,
                    [
                        Carbon::now($this->timezone)->subDays($value)->startOfDay(),
                        Carbon::now($this->timezone)->subDay()->endOfDay(),
                    ],
                    $aggregator
                );

                break;
            case Operators::PREVIOUS_X_DAYS_TO_DATE:
                $query->whereBetween(
                    $field,
                    [
                        Carbon::now($this->timezone)->subDays($value)->startOfDay(),
                        Carbon::now($this->timezone)->endOfDay(),
                    ],
                    $aggregator
                );

                break;
            case Operators::PAST:
                $query->where($field, '<=', Carbon::now(), $aggregator);

                break;
            case Operators::FUTURE:
                $query->where($field, '>=', Carbon::now(), $aggregator);

                break;
            case Operators::BEFORE_X_HOURS_AGO:
                $query->where($field, '<', Carbon::now($this->timezone)->subHours($value), $aggregator);

                break;
            case Operators::AFTER_X_HOURS_AGO:
                $query->where($field, '>', Carbon::now($this->timezone)->subHours($value), $aggregator);

                break;
            case Operators::YESTERDAY:
            case Operators::PREVIOUS_WEEK:
            case Operators::PREVIOUS_MONTH:
            case Operators::PREVIOUS_QUARTER:
            case Operators::PREVIOUS_YEAR:
            case Operators::PREVIOUS_WEEK_TO_DATE:
            case Operators::PREVIOUS_MONTH_TO_DATE:
            case Operators::PREVIOUS_QUARTER_TO_DATE:
            case Operators::PREVIOUS_YEAR_TO_DATE:
                $period = $operator === Operators::YESTERDAY ? 'Day' : Str::ucfirst(Str::of($operator)->explode('_')->get(1));
                $sub = 'sub' . $period;
                $start = 'startOf' . $period;
                $end = 'endOf' . $period;
                if (Str::endsWith($operator, 'To_Date')) {
                    $interval = [Carbon::now($this->timezone)->$start(), Carbon::now($this->timezone)];
                } else {
                    $interval = [Carbon::now($this->timezone)->$sub()->$start(), Carbon::now($this->timezone)->$sub()->$end()];
                }
                $query->whereBetween($field, $interval, $aggregator);

                break;
        }
    }

    private function computeMainOperator(ConditionTreeLeaf $conditionTreeLeaf, Builder $query, string $aggregator): void
    {
        $field = $this->formatField($conditionTreeLeaf->getField());
        $value = $conditionTreeLeaf->getValue();
        switch ($conditionTreeLeaf->getOperator()) {
            case Operators::BLANK:
                $query->whereNull(Str::replace('"', '', $field), $aggregator);

                break;
            case Operators::PRESENT:
                $query->whereNotNull(Str::replace('"', '', $field), $aggregator);

                break;
            case Operators::EQUAL:
            case Operators::NOT_EQUAL:
            case Operators::GREATER_THAN:
            case Operators::LESS_THAN:
                $query->where(Str::replace('"', '', $field), $this->basicSymbols[$conditionTreeLeaf->getOperator()], $value, $aggregator);

                break;
            case Operators::ICONTAINS:
                $query->where($field, 'ilike', '%'.$value.'%', $aggregator);

                break;
            case Operators::CONTAINS:
                $query->where($field, 'like', '%'.$value.'%', $aggregator);

                break;
            case Operators::NOT_CONTAINS:
                $query->where($field, 'not like', '%'.$value.'%', $aggregator);

                break;
            case Operators::IN:
                $value = is_array($value) ? $value : array_map('trim', explode(',', $value));
                $query->whereIn(Str::replace('"', '', $field), $value, $aggregator);

                break;
            case Operators::NOT_IN:
                $value = is_array($value) ? $value : array_map('trim', explode(',', $value));
                $query->whereNotIn(Str::replace('"', '', $field), $value, $aggregator);

                break;
            case Operators::STARTS_WITH:
                $query->where($field, 'like', $value.'%', $aggregator);

                break;
            case Operators::ENDS_WITH:
                $query->where($field, 'like', '%'.$value, $aggregator);

                break;
            case Operators::ISTARTS_WITH:
                $query->where($field, 'ilike', $value.'%', $aggregator);

                break;
            case Operators::IENDS_WITH:
                $query->where($field, 'ilike', '%'.$value, $aggregator);

                break;
        }
    }
}
