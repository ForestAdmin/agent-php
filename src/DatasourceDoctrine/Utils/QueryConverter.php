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
    )
    {
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

            foreach ($projectionGrouped as $alias => $projection) {
                $this->queryBuilder->addSelect('partial ' . $alias . '.{' . implode(',', $projection) . '}');
            }
        } else {
            $this->queryBuilder->select($this->mainAlias);
        }
    }

    private function applySort(): void
    {
        /** @var Sort $sort */
        if (method_exists($this->filter, 'getSort') && $sort = $this->filter->getSort()) {
            foreach ($sort->getFields() as $value) {
                if (!Str::contains($value['field'], ':')) {
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
        if (in_array($conditionTree->getOperator(), FrontendFilterable::BASE_DATEONLY_OPERATORS, true)) {
            return $this->computeDateOperator($conditionTree);
        } else {
            return $this->computeMainOperator($conditionTree);
        }
    }

    private function computeDateOperator(ConditionTreeLeaf $conditionTreeLeaf): string|Query\Expr\Comparison
    {
        $field = Str::contains($conditionTreeLeaf->getField(), ':')
            ? Str::replace(':', '.', $conditionTreeLeaf->getField())
            : $this->mainAlias . '.' . $conditionTreeLeaf->getField();
        $value = $conditionTreeLeaf->getValue();
        $operator = $conditionTreeLeaf->getOperator();
        $expr = $this->queryBuilder->expr();
        switch ($operator) {
            case 'Today':
                $this->addParameter(Carbon::now($this->timezone)->startOfDay());
                $from = "?$this->parameterIterator";
                $this->addParameter(Carbon::now($this->timezone)->endOfDay());
                $to = "?$this->parameterIterator";

                return $expr->between(
                    $field,
                    $from,
                    $to
                );
            case 'Before':
                $this->addParameter(new Carbon(new \DateTime($value), $this->timezone));

                return $expr->lt($field, "?$this->parameterIterator");
            case 'After':
                $this->addParameter(new Carbon(new \DateTime($value), $this->timezone));

                return $expr->gt($field, "?$this->parameterIterator");
            case'Previous_X_Days':
                //$this->ensureIntegerValue($value);
                // todo verify that the check below is done into the PHP agent
                $this->addParameter(Carbon::now($this->timezone)->subDays($value)->startOfDay());
                $from = "?$this->parameterIterator";
                $this->addParameter(Carbon::now($this->timezone)->subDay()->endOfDay());
                $to = "?$this->parameterIterator";

                return $expr->between(
                    $field,
                    $from,
                    $to
                );
            case'Previous_X_Days_To_Date':
                //$this->ensureIntegerValue($value);
                // todo verify that the check below is done into the PHP agent
                $this->addParameter(Carbon::now($this->timezone)->subDays($value)->startOfDay());
                $from = "?$this->parameterIterator";
                $this->addParameter(Carbon::now($this->timezone)->endOfDay());
                $to = "?$this->parameterIterator";

                return $expr->between(
                    $field,
                    $from,
                    $to
                );
            case'Past':
                $this->addParameter(new Carbon($this->timezone));

                return $expr->lte($field, "?$this->parameterIterator");
            case'Future':
                $this->addParameter(new Carbon($this->timezone));

                return $expr->gte($field, "?$this->parameterIterator");
            case'Before_X_Hours_Ago':
                //$this->ensureIntegerValue($value);
                // todo verify that the check below is done into the PHP agent
                $this->addParameter(Carbon::now($this->timezone)->subHours($value));

                return $expr->lt($field, "?$this->parameterIterator");
            case'After_X_Hours_Ago':
                //$this->ensureIntegerValue($value);
                // todo verify that the check below is done into the PHP agent
                $this->addParameter(Carbon::now($this->timezone)->subHours($value));

                return $expr->gt($field, "?$this->parameterIterator");
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

                return $expr->between(
                    $field,
                    $from,
                    $to
                );
            default :
                throw new \RuntimeException('Unknown operator');
        }
    }

    private function computeMainOperator(ConditionTreeLeaf $conditionTreeLeaf): string|Query\Expr\Func|Query\Expr\Comparison
    {
        $field = Str::contains($conditionTreeLeaf->getField(), ':')
            ? Str::replace(':', '.', $conditionTreeLeaf->getField())
            : $this->mainAlias . '.' . $conditionTreeLeaf->getField();
        $value = $conditionTreeLeaf->getValue();
        $expr = $this->queryBuilder->expr();

        switch ($conditionTreeLeaf->getOperator()) {
            case 'Blank':
                return $expr->isNull($field);
            case 'Present':
                return $expr->isNotNull($field);
            case 'Equal':
                $this->addParameter($value);

                return $expr->eq($field, "?$this->parameterIterator");
            case  'Not_Equal':
                $this->addParameter($value);

                return $expr->neq($field, "?$this->parameterIterator");
            case 'Greater_Than':
                $this->addParameter($value);

                return $expr->gt($field, "?$this->parameterIterator");
            case 'Less_Than':
                $this->addParameter($value);

                return $expr->lt($field, "?$this->parameterIterator");
            case 'IContains':
                return $expr->like(
                    $expr->lower($field),
                    $expr->lower($expr->literal('%' . $value . '%'))
                );
            case 'Contains':
                return $expr->like($field, $expr->literal('%' . $value . '%'));
            case 'Not_Contains':
                return $expr->notLike($field, $expr->literal('%' . $value . '%'));
            case 'In':
                $value = is_array($value) ? $value : array_map('trim', explode(',', $value));
                return $expr->in($field, $value);
            case 'Not_In':
                $value = is_array($value) ? $value : array_map('trim', explode(',', $value));
                return $expr->notIn($field, $value);
            case 'Starts_With':
                return $expr->like($field, $expr->literal($value . '%'));
            case 'Ends_With':
                return $expr->like($field, $expr->literal('%' . $value));
            case 'IStarts_With':
                return $expr->like(
                    $expr->lower($field),
                    $expr->lower($expr->literal($value . '%'))
                );
            case 'IEnds_With':
                return $expr->like(
                    $expr->lower($field),
                    $expr->lower($expr->literal('%' . $value))
                );
            case 'Missing':
                //todo what is it ?
            default :
                throw new \RuntimeException('Unknown operator');
        };
    }

    private function addParameter($value): void
    {
        $this->parameterIterator++;
        $this->parameters[] = new Parameter($this->parameterIterator, $value);
    }
}
