<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Charts;

use ForestAdmin\AgentPHP\Agent\Routes\AbstractCollectionRoute;
use ForestAdmin\AgentPHP\Agent\Utils\ContextFilterFactory;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\LeaderboardChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\LineChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\ObjectiveChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\PieChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\ValueChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\FilterFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\RelationSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Collection as CollectionUtils;
use Illuminate\Support\Str;

class Charts extends AbstractCollectionRoute
{
    protected Collection $collection;

    protected Filter $filter;

    protected string $type;

    /**
     * @return $this
     */
    public function setupRoutes(): self
    {
        $this->addRoute(
            'forest.chart',
            'post',
            '/stats/{collectionName}',
            fn ($args) => $this->handleRequest($args)
        );

        return $this;
    }

    public function handleRequest(array $args = []): array
    {
        $this->build($args);
        $this->permissions->canChart($this->request);
        $scope = $this->permissions->getScope($this->collection);
        $this->filter = ContextFilterFactory::build($this->collection, $this->request, $scope);
        $this->setType($this->request->get('type'));

        return [
            'renderChart' => true,
            'content'     => $this->{'make' . $this->type}(),
        ];
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $chartTypes = ['Value', 'Objective', 'Pie', 'Line', 'Leaderboard'];
        if (! in_array($type, $chartTypes, true)) {
            throw new ForestException("Invalid Chart type $type");
        }

        $this->type = $type;
    }

    private function makeValue(): ValueChart
    {
        $result = [
            'value'         => $this->computeValue($this->filter),
            'previousValue' => null,
        ];

        $isAndAggregator = $this->filter->getConditionTree() instanceof ConditionTreeBranch && $this->filter->getConditionTree()->getAggregator() === 'And';
        $withCountPrevious = (bool) $this->filter->getConditionTree()?->someLeaf(fn ($leaf) => $leaf->useIntervalOperator());

        if ($withCountPrevious && ! $isAndAggregator) {
            $result['previousValue'] = $this->computeValue(FilterFactory::getPreviousPeriodFilter($this->filter, $this->caller->getTimezone()));
        }

        return new ValueChart(...$result);
    }

    private function makeObjective(): ObjectiveChart
    {
        return new ObjectiveChart($this->computeValue($this->filter));
    }

    private function makePie(): PieChart
    {
        $aggregation = new Aggregation(
            operation: $this->request->get('aggregate'),
            field: $this->request->get('aggregate_field'),
            groups: $this->request->get('group_by_field') ? [['field' => $this->request->get('group_by_field')]] : []
        );
        $aggregate = Str::lower($this->request->get('aggregate'));

        $result = $this->collection->aggregate($this->caller, $this->filter, $aggregation, null, $this->type);

        return new PieChart($this->mapArrayToKeyValueAggregate($result, $aggregate));
    }

    private function makeLine(): LineChart
    {
        $aggregation = new Aggregation(
            operation: $this->request->get('aggregate'),
            field: $this->request->get('aggregate_field'),
            groups: [['field' => $this->request->get('group_by_date_field'), 'operation' => $this->request->get('time_range')]] // Todo it's useful to add operation ?
        );
        $aggregate = Str::lower($this->request->get('aggregate'));

        $result = $this->collection->aggregate($this->caller, $this->filter, $aggregation, null, $this->type);

        return new LineChart($this->mapArrayToKeyValueAggregateDate($result, $aggregate, $this->request->get('time_range')));
    }

    private function makeLeaderboard(): LeaderboardChart
    {
        /** @var RelationSchema $field */
        $field = $this->collection->getFields()[$this->request->get('relationship_field')];
        $foreignCollectionName = null;

        if ($field->getType() === 'OneToMany') {
            $foreignCollectionName = CollectionUtils::getInverseRelation($this->collection, $this->request->get('relationship_field'));
        }

        if ($field->getType() === 'ManyToMany') {
            $foreignCollectionName = CollectionUtils::getThroughTarget($this->collection, $this->request->get('relationship_field'));
        }

        $aggregation = new Aggregation(
            operation: $this->request->get('aggregate'),
            field: $this->request->get('aggregate_field'),
            groups: $this->request->get('label_field') ? [['field' => $this->request->get('label_field')]] : []
        );
        $aggregate = Str::lower($this->request->get('aggregate'));

        if (! $foreignCollectionName || ! $aggregation->getGroups()) {
            throw new ForestException('Failed to generate leaderboard chart: parameters do not match pre-requisites');
        }
        $filter = $this->filter->nest($foreignCollectionName);
        $result = $this->collection->aggregate($this->caller, $filter, $aggregation, $this->request->get('limit'), $this->type);

        return new LeaderboardChart($this->mapArrayToKeyValueAggregate($result, $aggregate));
    }

    private function computeValue(Filter $filter): int
    {
        $aggregation = new Aggregation(operation: $this->request->get('aggregate'), field: $this->request->get('aggregate_field'));
        $result = $this->collection->aggregate($this->caller, $filter, $aggregation, null ,$this->type);
        $rows = array_shift($result);

        return array_values($rows)[0] ?? 0;
    }

    private function mapArrayToKeyValueAggregate($array, string $aggregate): array
    {
        return collect($array)
            ->map(function ($item) use ($aggregate) {
                $keys = array_keys($item);
                if ($keys[0] === Str::lower($aggregate)) {
                    $key = $item[$keys[1]];
                    $value = $item[$keys[0]];
                } else {
                    $key = $item[$keys[0]];
                    $value = $item[$keys[1]];
                }

                return compact('key', 'value');
            })->toArray();
    }

    private function mapArrayToKeyValueAggregateDate($array, string $aggregate, string $aggregateIsADate): array
    {
        return collect($array)
            ->map(function ($item) use ($aggregate, $aggregateIsADate) {
                $keys = array_keys($item);
                if ($keys[0] === Str::lower($aggregate)) {
                    $label = $item[$keys[1]];
                    $values = $item[$keys[0]];
                } else {
                    $label = $item[$keys[0]];
                    $values = $item[$keys[1]];
                }

                $label = $label->format($this->getDateFormat($aggregateIsADate));

                return compact('label', 'values');
            })->toArray();
    }

    private function getDateFormat(string $field): string
    {
        $format = match (Str::lower($field)) {
            'day'   => 'd/m/Y',
            'week'  => '\WW-Y',
            'month' => 'M Y',
            'year'  => 'Y',
            default => '',
        };

        return $format;
    }
}
