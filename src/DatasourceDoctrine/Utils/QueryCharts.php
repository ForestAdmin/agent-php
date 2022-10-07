<?php

namespace ForestAdmin\AgentPHP\DatasourceDoctrine\Utils;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use Illuminate\Support\Str;

class QueryCharts
{
    protected string $aggregate;

    protected string $field;

    public function __construct(
        protected ClassMetadata $entityMetadata,
        protected QueryBuilder $builder,
        protected Aggregation $aggregation,
        protected ?int $limit = null)
    {
        $this->aggregate = strtolower($aggregation->getOperation());
        $this->field = Str::contains($aggregation->getField(), ':')
            ? Str::replace(':', '.', $aggregation->getField())
            : Str::lower($entityMetadata->reflClass->getShortName()) . ($aggregation->getField() ? '.' . $aggregation->getField() : null);
    }

    public static function of(ClassMetadata $entityMetadata, QueryBuilder $builder, Aggregation $aggregation, ?int $limit = null): self
    {
        return (new static($entityMetadata, $builder, $aggregation, $limit));
    }

    public function queryValue(): QueryBuilder
    {
        return $this->builder->select("$this->aggregate($this->field) AS $this->aggregate");
    }

    public function queryObjective(): QueryBuilder
    {
        return $this->builder->select("$this->aggregate($this->field) AS $this->aggregate");
    }

    public function queryPie(): QueryBuilder
    {
        $groupField = $this->formatField($this->aggregation->getGroups()[0]['field']);

        return $this->builder->select("$groupField, $this->aggregate($this->field) AS $this->aggregate")
            ->groupBy($groupField);
    }

    public function queryLine(): QueryBuilder
    {
        $groupField = $this->formatField($this->aggregation->getGroups()[0]['field']);

        return $this->builder->select("$groupField, $this->aggregate($this->field) AS $this->aggregate")
            ->groupBy($groupField);

        // todo , it's necessary to add `where groupfield is not null` like version 1 ?
    }

    public function queryLeaderboard(): QueryBuilder
    {
        $groupField = $this->formatField($this->aggregation->getGroups()[0]['field']);

        return $this->builder->select("$groupField, $this->aggregate($this->field) AS $this->aggregate")
            ->addOrderBy($this->aggregate, 'DESC')
            ->groupBy($groupField)
            ->setMaxResults($this->limit);
    }

    public function formatField(string $originalField): string
    {
        return Str::contains($originalField, ':')
            ? Str::replace(':', '.', $originalField)
            : Str::lower($this->entityMetadata->reflClass->getShortName()) . '.' . $originalField;
    }
}
