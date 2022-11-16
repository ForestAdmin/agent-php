<?php

namespace ForestAdmin\AgentPHP\Agent\Utils;

use Doctrine\ORM\Query;
use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\FrontendFilterable;
use ForestAdmin\AgentPHP\DatasourceDoctrine\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTree;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Page;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Sort;
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

    protected string $mainAlias;

    protected string $tableName;

    public function __construct(
        protected Collection  $collection,
        protected Filter      $filter,
        protected string      $timezone,
        protected ?Projection $projection = null,
    ) {
        $this->tableName = $this->collection->getTableName();
        $this->build();
    }

    public static function of(Collection $collection, Filter $filter, string $timezone, ?Projection $projection = null): Builder
    {
        return (new static($collection, $filter, $timezone, $projection))->query;
    }

    private function build(): void
    {
        $this->query = $this->collection
            ->getDataSource()
            ->getOrm()
            ->getConnection()
            ->table($this->tableName, $this->tableName);

        $this->applyProjection();

        $this->applySort();

        $this->applyPagination();

//        $this->applyConditionTree();
    }

    private function applyProjection(): void
    {
        $selectRaw = '';
        if ($this->projection) {
            $selectRaw .= collect($this->projection->columns())->map(fn ($field) => "$this->tableName.$field")->implode(', ');
            foreach ($this->projection->relations() as $relation => $relationFields) {
                /** @var RelationSchema $relation */
                $relation = $this->collection->getFields()[$relation];
                $relationTableName = $this->collection->getDataSource()->getCollection($relation->getForeignCollection())->getTableName();
                $this->addJoinRelation($relation, $relationTableName);
                $selectRaw .= ', ' . $relationFields->map(fn ($field) => "$relationTableName.$field as \"$relationTableName.$field\"")->implode(', ');
            }
        } else {
            $selectRaw = "$this->tableName.*";
        }

        $this->query->selectRaw($selectRaw);
    }

    private function addJoinRelation(RelationSchema $relation, string $relationTableName): void
    {
        if ($relation instanceof OneToOneSchema || $relation instanceof OneToManySchema) {
            $this->query->leftJoin(
                $relationTableName,
                $this->tableName . '.' . $relation->getOriginKey(),
                '=',
                $relationTableName . '.' . $relation->getOriginKeyTarget()
            );
        } elseif ($relation instanceof ManyToOneSchema) {
            $this->query->leftJoin(
                $relationTableName,
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

//    private function applyConditionTree(): void
//    {
//        if ($conditionTree = $this->filter->getConditionTree()) {
//            $this->queryBuilder->where($this->convertConditionTree($conditionTree));
//        }
//    }
//
//    private function convertConditionTree(ConditionTree $conditionTree): Query\Expr\Orx|Query\Expr\Andx|string|Query\Expr\Func|Query\Expr\Comparison
//    {
//        if ($conditionTree instanceof ConditionTreeBranch) {
//            $expr = [];
//            foreach ($conditionTree->getConditions() as $condition) {
//                $expr[] = $this->convertConditionTree($condition);
//            }
//
//            if ($conditionTree->getAggregator() === 'And') {
//                return $this->queryBuilder->expr()->andX(...$expr);
//            } else {
//                return $this->queryBuilder->expr()->orX(...$expr);
//            }
//        }
//
//        /** @var ConditionTreeLeaf $conditionTree */
//        if (Str::contains($conditionTree->getField(), ':')) {
//            $relation = Str::before($conditionTree->getField(), ':');
//            $this->addJoin($relation);
//        }
//        if (in_array($conditionTree->getOperator(), FrontendFilterable::BASE_DATEONLY_OPERATORS, true)) {
//            return $this->computeDateOperator($conditionTree);
//        } else {
//            return $this->computeMainOperator($conditionTree);
//        }
//    }
//
//    private function computeDateOperator(ConditionTreeLeaf $conditionTreeLeaf): string|Query\Expr\Comparison
//    {
//        $field = Str::contains($conditionTreeLeaf->getField(), ':')
//            ? Str::replace(':', '.', $conditionTreeLeaf->getField())
//            : $this->mainAlias . '.' . $conditionTreeLeaf->getField();
//        $value = $conditionTreeLeaf->getValue();
//        $operator = $conditionTreeLeaf->getOperator();
//        $baseExpr = $this->queryBuilder->expr();
//        switch ($operator) {
//            case 'Today':
//                $this->addParameter(Carbon::now($this->timezone)->startOfDay());
//                $from = "?$this->parameterIterator";
//                $this->addParameter(Carbon::now($this->timezone)->endOfDay());
//                $to = "?$this->parameterIterator";
//
//                $expr = $baseExpr->between(
//                    $field,
//                    $from,
//                    $to
//                );
//
//                break;
//            case 'Before':
//                $this->addParameter(new Carbon(new \DateTime($value), $this->timezone));
//
//                $expr = $baseExpr->lt($field, "?$this->parameterIterator");
//
//                break;
//            case 'After':
//                $this->addParameter(new Carbon(new \DateTime($value), $this->timezone));
//
//                $expr = $baseExpr->gt($field, "?$this->parameterIterator");
//
//                break;
//            case'Previous_X_Days':
//                //$this->ensureIntegerValue($value);
//                // todo verify that the check below is done into the PHP agent
//                $this->addParameter(Carbon::now($this->timezone)->subDays($value)->startOfDay());
//                $from = "?$this->parameterIterator";
//                $this->addParameter(Carbon::now($this->timezone)->subDay()->endOfDay());
//                $to = "?$this->parameterIterator";
//
//                $expr = $baseExpr->between(
//                    $field,
//                    $from,
//                    $to
//                );
//
//                break;
//            case'Previous_X_Days_To_Date':
//                //$this->ensureIntegerValue($value);
//                // todo verify that the check below is done into the PHP agent
//                $this->addParameter(Carbon::now($this->timezone)->subDays($value)->startOfDay());
//                $from = "?$this->parameterIterator";
//                $this->addParameter(Carbon::now($this->timezone)->endOfDay());
//                $to = "?$this->parameterIterator";
//
//                $expr = $baseExpr->between(
//                    $field,
//                    $from,
//                    $to
//                );
//
//                break;
//            case'Past':
//                $this->addParameter(new Carbon($this->timezone));
//
//                $expr = $baseExpr->lte($field, "?$this->parameterIterator");
//
//                break;
//            case'Future':
//                $this->addParameter(new Carbon($this->timezone));
//
//                $expr = $baseExpr->gte($field, "?$this->parameterIterator");
//
//                break;
//            case'Before_X_Hours_Ago':
//                //$this->ensureIntegerValue($value);
//                // todo verify that the check below is done into the PHP agent
//                $this->addParameter(Carbon::now($this->timezone)->subHours($value));
//
//                $expr = $baseExpr->lt($field, "?$this->parameterIterator");
//
//                break;
//            case'After_X_Hours_Ago':
//                //$this->ensureIntegerValue($value);
//                // todo verify that the check below is done into the PHP agent
//                $this->addParameter(Carbon::now($this->timezone)->subHours($value));
//
//                $expr = $baseExpr->gt($field, "?$this->parameterIterator");
//
//                break;
//            case 'Yesterday':
//            case 'Previous_Week':
//            case 'Previous_Month':
//            case 'Previous_Quarter':
//            case 'Previous_Year':
//            case 'Previous_Week_To_Date':
//            case 'Previous_Month_To_Date':
//            case 'Previous_Quarter_To_Date':
//            case 'Previous_Year_To_Date':
//                $period = $operator === 'Yesterday' ? 'Day' : Str::ucfirst(Str::of($operator)->explode('_')->get(1));
//                $sub = 'sub' . $period;
//                $start = 'startOf' . $period;
//                $end = 'endOf' . $period;
//                if (Str::endsWith($operator, 'To_Date')) {
//                    $this->addParameter(Carbon::now($this->timezone)->$start());
//                    $from = "?$this->parameterIterator";
//                    $this->addParameter(Carbon::now($this->timezone));
//                    $to = "?$this->parameterIterator";
//                } else {
//                    $this->addParameter(Carbon::now($this->timezone)->$sub()->$start());
//                    $from = "?$this->parameterIterator";
//                    $this->addParameter(Carbon::now($this->timezone)->$sub()->$end());
//                    $to = "?$this->parameterIterator";
//                }
//
//                $expr = $baseExpr->between(
//                    $field,
//                    $from,
//                    $to
//                );
//
//                break;
//            default:
//                throw new \RuntimeException('Unknown operator');
//        }
//
//        return $expr;
//    }
//
//    private function computeMainOperator(ConditionTreeLeaf $conditionTreeLeaf): string|Query\Expr\Func|Query\Expr\Comparison
//    {
//        $field = Str::contains($conditionTreeLeaf->getField(), ':')
//            ? Str::replace(':', '.', $conditionTreeLeaf->getField())
//            : $this->mainAlias . '.' . $conditionTreeLeaf->getField();
//        $value = $conditionTreeLeaf->getValue();
//        $baseExpr = $this->queryBuilder->expr();
//
//        switch ($conditionTreeLeaf->getOperator()) {
//            case 'Blank':
//                $expr = $baseExpr->isNull($field);
//
//                break;
//            case 'Present':
//                $expr = $baseExpr->isNotNull($field);
//
//                break;
//            case 'Equal':
//                $this->addParameter($value);
//
//                $expr = $baseExpr->eq($field, "?$this->parameterIterator");
//
//                break;
//            case  'Not_Equal':
//                $this->addParameter($value);
//
//                $expr = $baseExpr->neq($field, "?$this->parameterIterator");
//
//                break;
//            case 'Greater_Than':
//                $this->addParameter($value);
//
//                $expr = $baseExpr->gt($field, "?$this->parameterIterator");
//
//                break;
//            case 'Less_Than':
//                $this->addParameter($value);
//
//                $expr = $baseExpr->lt($field, "?$this->parameterIterator");
//
//                break;
//            case 'IContains':
//                $expr = $baseExpr->like(
//                    $baseExpr->lower($field),
//                    $baseExpr->lower($baseExpr->literal('%' . $value . '%'))
//                );
//
//                break;
//            case 'Contains':
//                $expr = $baseExpr->like($field, $baseExpr->literal('%' . $value . '%'));
//
//                break;
//            case 'Not_Contains':
//                $expr = $baseExpr->notLike($field, $baseExpr->literal('%' . $value . '%'));
//
//                break;
//            case 'In':
//                $value = is_array($value) ? $value : array_map('trim', explode(',', $value));
//
//                $expr = $baseExpr->in($field, $value);
//
//                break;
//            case 'Not_In':
//                $value = is_array($value) ? $value : array_map('trim', explode(',', $value));
//
//                $expr = $baseExpr->notIn($field, $value);
//
//                break;
//            case 'Starts_With':
//                $expr = $baseExpr->like($field, $baseExpr->literal($value . '%'));
//
//                break;
//            case 'Ends_With':
//                $expr = $baseExpr->like($field, $baseExpr->literal('%' . $value));
//
//                break;
//            case 'IStarts_With':
//                $expr = $baseExpr->like(
//                    $baseExpr->lower($field),
//                    $baseExpr->lower($baseExpr->literal($value . '%'))
//                );
//
//                break;
//            case 'IEnds_With':
//                $expr = $baseExpr->like(
//                    $baseExpr->lower($field),
//                    $baseExpr->lower($baseExpr->literal('%' . $value))
//                );
//
//                break;
//            default:
//                throw new \RuntimeException('Unknown operator');
//        }
//
//        return $expr;
//    }
}
