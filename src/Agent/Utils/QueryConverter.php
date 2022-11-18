<?php

namespace ForestAdmin\AgentPHP\Agent\Utils;

use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\FrontendFilterable;
use ForestAdmin\AgentPHP\DatasourceDoctrine\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTree;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Page;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Sort;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\RelationSchema;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class QueryConverter
{
    protected Builder $query;

    protected string $tableName;

    protected array $basicSymbols = [
        'Equal'        => '=',
        'Not_Equal'    => '!=',
        'Greater_Than' => '>',
        'Less_Than'    => '<',
    ];

    public function __construct(
        protected Collection  $collection,
        protected string      $timezone,
        protected ?Filter      $filter = null,
        protected ?Projection $projection = null,
    ) {
        $this->tableName = $this->collection->getTableName();
        $this->build();
    }

    public static function of(Collection $collection, string $timezone, ?Filter $filter = null, ?Projection $projection = null): Builder
    {
        return (new static($collection, $timezone, $filter, $projection))->query;
    }

    private function build(): void
    {
        $this->query = $this->collection
            ->getDataSource()
            ->getOrm()
            ->getConnection()
            ->table($this->tableName, $this->tableName);

        $this->applyProjection();

        if ($this->filter) {
            $this->applySort();

            $this->applyPagination();

            $this->applyConditionTree();
        }
    }

    private function applyProjection(): void
    {
        if ($this->projection) {
            $selectRaw = collect($this->projection->columns())
                ->map(fn ($field) => "\"$this->tableName\".\"$field\"")
                ->implode(', ');

            foreach ($this->projection->relations() as $relation => $relationFields) {
                /** @var RelationSchema $relation */
                $relationSchema = $this->collection->getFields()[$relation];
                $relationTableName = $this->collection->getDataSource()->getCollection($relationSchema->getForeignCollection())->getTableName();
                $this->addJoinRelation($relationSchema, $relationTableName);
                $selectRaw .= ', ' . $relationFields->map(fn ($field) => "\"$relationTableName\".\"$field\" as \"$relation.$field\"")->implode(', ');
            }

            $this->query->selectRaw($selectRaw);
        }
    }

    private function addJoinRelation(RelationSchema $relation, string $relationTableName): void
    {
        if ($relation instanceof OneToOneSchema || $relation instanceof OneToManySchema) {
            $this->query->leftJoin(
                "$relationTableName as $relationTableName",
                $this->tableName . '.' . $relation->getOriginKey(),
                '=',
                $relationTableName . '.' . $relation->getOriginKeyTarget()
            );
        } elseif ($relation instanceof ManyToOneSchema) {
            $this->query->leftJoin(
                "$relationTableName as $relationTableName",
                $this->tableName . '.' . $relation->getForeignKey(),
                '=',
                $relationTableName . '.' . $relation->getForeignKeyTarget()
            );
        } else {
            // ManyToMany case
        }
    }

    private function applySort(): void
    {
        /** @var Sort $sort */
        if (method_exists($this->filter, 'getSort') && $sort = $this->filter->getSort()) {
            foreach ($sort as $value) {
                if (! Str::contains($value['field'], ':')) {
                    $this->query->orderBy($this->tableName . '.' . $value['field'], $value['ascending'] ? 'ASC' : 'DESC');
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
        $field = Str::contains($conditionTreeLeaf->getField(), ':')
            ? Str::replace(':', '.', $conditionTreeLeaf->getField())
            : $this->tableName . '.' . $conditionTreeLeaf->getField();
        $value = $conditionTreeLeaf->getValue();
        $operator = $conditionTreeLeaf->getOperator();

        switch ($operator) {
            case 'yesterday':
            case 'previous_week':
            case 'previous_month':
            case 'previous_quarter':
            case 'previous_year':
            case 'previous_week_to_date':
            case 'previous_month_to_date':
            case 'previous_quarter_to_date':
            case 'previous_year_to_date':

                break;
        }

        switch ($operator) {
            case 'Today':
                $query->whereBetween(
                    $field,
                    [
                        Carbon::now($this->timezone)->startOfDay(),
                        Carbon::now($this->timezone)->endOfDay(),
                    ],
                    $aggregator
                );

                break;
            case 'Before':
                $query->where($field, '<', new Carbon(new \DateTime($value), $this->timezone), $aggregator);

                break;
            case 'After':
                $query->where($field, '>', new Carbon(new \DateTime($value), $this->timezone), $aggregator);

                break;
            case'Previous_X_Days':
                $query->whereBetween(
                    $field,
                    [
                        Carbon::now($this->timezone)->subDays($value)->startOfDay(),
                        Carbon::now($this->timezone)->subDay()->endOfDay(),
                    ],
                    $aggregator
                );

                break;
            case'Previous_X_Days_To_Date':
                $query->whereBetween(
                    $field,
                    [
                        Carbon::now($this->timezone)->subDays($value)->startOfDay(),
                        Carbon::now($this->timezone)->endOfDay(),
                    ],
                    $aggregator
                );

                break;
            case'Past':
                $query->where($field, '<=', Carbon::now(), $aggregator);

                break;
            case'Future':
                $query->where($field, '>=', Carbon::now(), $aggregator);

                break;
            case'Before_X_Hours_Ago':
                $query->where($field, '<', Carbon::now($this->timezone)->subHours($value), $aggregator);

                break;
            case'After_X_Hours_Ago':
                $query->where($field, '>', Carbon::now($this->timezone)->subHours($value), $aggregator);

                break;
            case 'Yesterday':
            case 'Previous_Week':
            case 'Previous_Month':
            case 'Previous_Quarter':
            case 'Previous_Year':
            case 'Previous_Week_To_Date':
            case 'Previous_Month_To_Date':
            case 'Previous_Quarter_To_Date':
            case 'Previous_Year_To_Date':
                $period = $operator === 'Yesterday' ? 'Day' : Str::ucfirst(Str::of($operator)->explode('_')->get(1));
                $sub = 'sub' . $period;
                $start = 'startOf' . $period;
                $end = 'endOf' . $period;
                if (Str::endsWith($operator, 'To_Date')) {
                    $interval = [Carbon::now($this->timezone)->$start(), Carbon::now()];
                } else {
                    $interval = [Carbon::now($this->timezone)->$sub()->$start(), Carbon::now($this->timezone)->$sub()->$end()];
                }
                $query->whereBetween($field, $interval, $aggregator);

                break;
            default:
                throw new \RuntimeException('Unknown operator');
        }
    }

    private function computeMainOperator(ConditionTreeLeaf $conditionTreeLeaf, Builder $query, string $aggregator): void
    {
        $field = Str::contains($conditionTreeLeaf->getField(), ':')
            ? Str::replace(':', '.', $conditionTreeLeaf->getField())
            : $this->tableName . '.' . $conditionTreeLeaf->getField();
        $value = $conditionTreeLeaf->getValue();
        switch ($conditionTreeLeaf->getOperator()) {
            case 'Blank':
                $query->whereNull($field, $aggregator);

                break;
            case 'Present':
                $query->whereNotNull($field, $aggregator);

                break;
            case 'Equal':
            case 'Not_Equal':
            case 'Greater_Than':
            case 'Less_Than':
                $query->where($field, $this->basicSymbols[$conditionTreeLeaf->getOperator()], $value, $aggregator);

                break;
            case 'IContains':
                $query->whereRaw("LOWER ($field) LIKE LOWER(?)", ['%' . $value . '%'], $aggregator);

                break;
            case 'Contains':
                $query->whereRaw("$field LIKE ?", ['%' . $value . '%'], $aggregator);

                break;
            case 'Not_Contains':
                $query->whereRaw("$field NOT LIKE ?", ['%' . $value . '%'], $aggregator);

                break;
            case 'In':
                $value = is_array($value) ? $value : array_map('trim', explode(',', $value));
                $query->whereIn($field, $value, $aggregator);

                break;
            case 'Not_In':
                $value = is_array($value) ? $value : array_map('trim', explode(',', $value));
                $query->whereNotIn($field, $value, $aggregator);

                break;
            case 'Starts_With':
                $query->whereRaw("$field LIKE ?", [$value . '%'], $aggregator);

                break;
            case 'Ends_With':
                $query->whereRaw("$field LIKE ?", ['%' . $value], $aggregator);

                break;
            case 'IStarts_With':
                $query->whereRaw("LOWER ($field) LIKE ?", [$value . '%'], $aggregator);

                break;
            case 'IEnds_With':
                $query->whereRaw("LOWER ($field) LIKE LOWER(?)", ['%' . $value], $aggregator);

                break;
            default:
                throw new ForestException('Unknown operator');
        }
    }
}
