<?php

namespace ForestAdmin\AgentPHP\DatasourceDoctrine\Utils;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectRepository;
use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\FrontendFilterable;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTree;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Page;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Sort;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class QueryConverter
{
    protected QueryBuilder $queryBuilder;

    protected Query $query;

    protected array $parameters = [];

    protected int $parameterIterator = 0;

    protected ObjectRepository $repository;

    protected string $mainAlias;

    public function __construct(
        protected Filter                 $filter,
        protected EntityManagerInterface $entityManager,
        protected ClassMetadata          $entityMetadata,
        protected string                 $timezone,
        protected ?Projection            $projection = null,
    ) {
        $this->mainAlias = Str::lower($this->entityMetadata->reflClass->getShortName());
        $this->repository = $this->entityManager->getRepository($this->entityMetadata->getName());

        $this->build();
    }

    public static function of(Filter $filter, EntityManagerInterface $entityManager, ClassMetadata $entityMetadata, string $timezone, ?Projection $projection = null): QueryBuilder
    {
        return (new static($filter, $entityManager, $entityMetadata, $timezone, $projection))->queryBuilder;
    }

    public static function execute(Filter $filter, EntityManagerInterface $entityManager, ClassMetadata $entityMetadata, ?Projection $projection = null): array
    {
        $static = new static($filter, $entityManager, $entityMetadata, $projection);
        $sortData = $static->getSortData();
        $paginationData = $static->getPaginationData();

        return $static
            ->repository
            ->findBy(
                [],
                $sortData,
                $paginationData['limit'] ?: null,
                $paginationData['offset'] ?: null,
            );
    }

    private function build(): void
    {
        $this->queryBuilder = $this->entityManager->createQueryBuilder()->from($this->entityMetadata->getName(), $this->mainAlias);

        $this->applyProjection();

        $this->applySort();

        $this->applyPagination();

        $this->applyConditionTree();

        $this->queryBuilder->setParameters(new ArrayCollection($this->parameters));
    }

    private function applyProjection(): void
    {
        if ($this->projection) {
            foreach (array_keys($this->projection->relations()) as $relation) {
                $this->queryBuilder->leftJoin($this->mainAlias . '.' . $relation, $relation);
            }
            $projectionGrouped = $this->groupProjection();

            foreach ($projectionGrouped as $alias => $projection) {
                $this->queryBuilder->addSelect('partial ' . $alias . '.{' . implode(',', $projection) . '}');
            }
        } else {
            $this->queryBuilder->select($this->mainAlias);
        }
    }

    private function groupProjection(): array
    {
        $projectionGrouped = [];
        foreach ($this->projection as $field) {
            if (Str::contains($field, ':')) {
                $relation = Str::before($field, ':');
                $field = Str::after($field, ':');
                $className = $this->entityMetadata->getAssociationMapping($relation)['targetEntity'];
                $relationMetadata = AgentFactory::get('orm')->getMetadataFactory()->getMetadataFor($className);

                if (! in_array($field, $relationMetadata->getIdentifier(), true)) {
                    $projectionGrouped[$relation] = array_merge(
                        $projectionGrouped[$relation],
                        $relationMetadata->getIdentifier()
                    );
                } else {
                    $projectionGrouped[$relation][] = Str::after($field, ':');
                }
            } else {
                $projectionGrouped[$this->mainAlias][] = $field;
            }
        }

        return $projectionGrouped;
    }

    private function applySort(): void
    {
        /** @var Sort $sort */
        if (method_exists($this->filter, 'getSort') && $sort = $this->filter->getSort()) {
            foreach ($sort->getFields() as $value) {
                if (! Str::contains($value['field'], ':')) {
                    $this->queryBuilder->orderBy($this->mainAlias . '.' . $value['field'], $value['order']);
                } else {
                    $this->queryBuilder->orderBy(
                        Str::before($value['field'], ':') . '.' . Str::after($value['field'], ':'),
                        $value['order']
                    );
                }
            }
        }
    }

    private function applyPagination(): void
    {
        /** @var Page $page */
        if (method_exists($this->filter, 'getPage') && $page = $this->filter->getPage()) {
            $this->queryBuilder->setFirstResult($page->getOffset())
                ->setMaxResults($page->getLimit());
        }
    }

    private function applySearch(): void
    {
        // Search convert into conditionTree by Decorator
    }

    private function applyConditionTree(): void
    {
        if ($conditionTree = $this->filter->getConditionTree()) {
            $this->queryBuilder->where($this->convertConditionTree($conditionTree));
        }
    }

    private function convertConditionTree(ConditionTree $conditionTree): Query\Expr\Orx|Query\Expr\Andx|string|Query\Expr\Func|Query\Expr\Comparison
    {
        if ($conditionTree instanceof ConditionTreeBranch) {
            $expr = [];
            foreach ($conditionTree->getConditions() as $condition) {
                $expr[] = $this->convertConditionTree($condition);
            }

            if ($conditionTree->getAggregator() === 'And') {
                return $this->queryBuilder->expr()->andX(...$expr);
            } else {
                return $this->queryBuilder->expr()->orX(...$expr);
            }
        }

        /** @var ConditionTreeLeaf $conditionTree */
        if (Str::contains($conditionTree->getField(), ':')) {
            $relation = Str::before($conditionTree->getField(), ':');
            $this->addJoin($relation);
        }
        if (in_array($conditionTree->getOperator(), FrontendFilterable::BASE_DATEONLY_OPERATORS, true)) {
            return $this->computeDateOperator($conditionTree);
        } else {
            return $this->computeMainOperator($conditionTree);
        }
    }

    private function addJoin(string $relation): void
    {
        $addJoin = true;
        foreach ($this->queryBuilder->getDQLPart('join')[$this->mainAlias] as $join) {
            if ($join->getAlias() === $relation) {
                $addJoin = false;
            }
        }
        if ($addJoin) {
            $this->queryBuilder->leftJoin($this->mainAlias . '.' . $relation, $relation);
        }
    }

    private function computeDateOperator(ConditionTreeLeaf $conditionTreeLeaf): string|Query\Expr\Comparison
    {
        $field = Str::contains($conditionTreeLeaf->getField(), ':')
            ? Str::replace(':', '.', $conditionTreeLeaf->getField())
            : $this->mainAlias . '.' . $conditionTreeLeaf->getField();
        $value = $conditionTreeLeaf->getValue();
        $operator = $conditionTreeLeaf->getOperator();
        $baseExpr = $this->queryBuilder->expr();
        switch ($operator) {
            case 'Today':
                $this->addParameter(Carbon::now($this->timezone)->startOfDay());
                $from = "?$this->parameterIterator";
                $this->addParameter(Carbon::now($this->timezone)->endOfDay());
                $to = "?$this->parameterIterator";

                $expr = $baseExpr->between(
                    $field,
                    $from,
                    $to
                );

                break;
            case 'Before':
                $this->addParameter(new Carbon(new \DateTime($value), $this->timezone));

                $expr = $baseExpr->lt($field, "?$this->parameterIterator");

                break;
            case 'After':
                $this->addParameter(new Carbon(new \DateTime($value), $this->timezone));

                $expr = $baseExpr->gt($field, "?$this->parameterIterator");

                break;
            case'Previous_X_Days':
                //$this->ensureIntegerValue($value);
                // todo verify that the check below is done into the PHP agent
                $this->addParameter(Carbon::now($this->timezone)->subDays($value)->startOfDay());
                $from = "?$this->parameterIterator";
                $this->addParameter(Carbon::now($this->timezone)->subDay()->endOfDay());
                $to = "?$this->parameterIterator";

                $expr = $baseExpr->between(
                    $field,
                    $from,
                    $to
                );

                break;
            case'Previous_X_Days_To_Date':
                //$this->ensureIntegerValue($value);
                // todo verify that the check below is done into the PHP agent
                $this->addParameter(Carbon::now($this->timezone)->subDays($value)->startOfDay());
                $from = "?$this->parameterIterator";
                $this->addParameter(Carbon::now($this->timezone)->endOfDay());
                $to = "?$this->parameterIterator";

                $expr = $baseExpr->between(
                    $field,
                    $from,
                    $to
                );

                break;
            case'Past':
                $this->addParameter(new Carbon($this->timezone));

                $expr = $baseExpr->lte($field, "?$this->parameterIterator");

                break;
            case'Future':
                $this->addParameter(new Carbon($this->timezone));

                $expr = $baseExpr->gte($field, "?$this->parameterIterator");

                break;
            case'Before_X_Hours_Ago':
                //$this->ensureIntegerValue($value);
                // todo verify that the check below is done into the PHP agent
                $this->addParameter(Carbon::now($this->timezone)->subHours($value));

                $expr = $baseExpr->lt($field, "?$this->parameterIterator");

                break;
            case'After_X_Hours_Ago':
                //$this->ensureIntegerValue($value);
                // todo verify that the check below is done into the PHP agent
                $this->addParameter(Carbon::now($this->timezone)->subHours($value));

                $expr = $baseExpr->gt($field, "?$this->parameterIterator");

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
                    $this->addParameter(Carbon::now($this->timezone)->$start());
                    $from = "?$this->parameterIterator";
                    $this->addParameter(Carbon::now($this->timezone));
                    $to = "?$this->parameterIterator";
                } else {
                    $this->addParameter(Carbon::now($this->timezone)->$sub()->$start());
                    $from = "?$this->parameterIterator";
                    $this->addParameter(Carbon::now($this->timezone)->$sub()->$end());
                    $to = "?$this->parameterIterator";
                }

                $expr = $baseExpr->between(
                    $field,
                    $from,
                    $to
                );

                break;
            default:
                throw new \RuntimeException('Unknown operator');
        }

        return $expr;
    }

    private function computeMainOperator(ConditionTreeLeaf $conditionTreeLeaf): string|Query\Expr\Func|Query\Expr\Comparison
    {
        $field = Str::contains($conditionTreeLeaf->getField(), ':')
            ? Str::replace(':', '.', $conditionTreeLeaf->getField())
            : $this->mainAlias . '.' . $conditionTreeLeaf->getField();
        $value = $conditionTreeLeaf->getValue();
        $baseExpr = $this->queryBuilder->expr();

        switch ($conditionTreeLeaf->getOperator()) {
            case 'Blank':
                $expr = $baseExpr->isNull($field);

                break;
            case 'Present':
                $expr = $baseExpr->isNotNull($field);

                break;
            case 'Equal':
                $this->addParameter($value);

                $expr = $baseExpr->eq($field, "?$this->parameterIterator");

                break;
            case  'Not_Equal':
                $this->addParameter($value);

                $expr = $baseExpr->neq($field, "?$this->parameterIterator");

                break;
            case 'Greater_Than':
                $this->addParameter($value);

                $expr = $baseExpr->gt($field, "?$this->parameterIterator");

                break;
            case 'Less_Than':
                $this->addParameter($value);

                $expr = $baseExpr->lt($field, "?$this->parameterIterator");

                break;
            case 'IContains':
                $expr = $baseExpr->like(
                    $baseExpr->lower($field),
                    $baseExpr->lower($baseExpr->literal('%' . $value . '%'))
                );

                break;
            case 'Contains':
                $expr = $baseExpr->like($field, $baseExpr->literal('%' . $value . '%'));

                break;
            case 'Not_Contains':
                $expr = $baseExpr->notLike($field, $baseExpr->literal('%' . $value . '%'));

                break;
            case 'In':
                $value = is_array($value) ? $value : array_map('trim', explode(',', $value));

                $expr = $baseExpr->in($field, $value);

                break;
            case 'Not_In':
                $value = is_array($value) ? $value : array_map('trim', explode(',', $value));

                $expr = $baseExpr->notIn($field, $value);

                break;
            case 'Starts_With':
                $expr = $baseExpr->like($field, $baseExpr->literal($value . '%'));

                break;
            case 'Ends_With':
                $expr = $baseExpr->like($field, $baseExpr->literal('%' . $value));

                break;
            case 'IStarts_With':
                $expr = $baseExpr->like(
                    $baseExpr->lower($field),
                    $baseExpr->lower($baseExpr->literal($value . '%'))
                );

                break;
            case 'IEnds_With':
                $expr = $baseExpr->like(
                    $baseExpr->lower($field),
                    $baseExpr->lower($baseExpr->literal('%' . $value))
                );

                break;
            default:
                throw new \RuntimeException('Unknown operator');
        }

        return $expr;
    }

    private function addParameter($value): void
    {
        $this->parameterIterator++;
        $this->parameters[] = new Parameter($this->parameterIterator, $value);
    }
}
