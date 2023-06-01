<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Charts;

use ForestAdmin\AgentPHP\Agent\Facades\JsonApi;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractCollectionRoute;
use ForestAdmin\AgentPHP\Agent\Utils\ContextFilterFactory;
use ForestAdmin\AgentPHP\Agent\Utils\ContextVariablesInjector;
use ForestAdmin\AgentPHP\Agent\Utils\ContextVariablesInstantiator;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\LeaderboardChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\LineChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\ObjectiveChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\PieChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\ValueChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\FilterFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\RelationSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Collection as CollectionUtils;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Charts extends AbstractCollectionRoute
{
    protected CollectionContract $collection;

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
        $this->injectContextVariables();
        $this->filter = ContextFilterFactory::build($this->collection, $this->request, $scope);
        $this->setType($this->request->get('type'));
        $this->setCaller(QueryStringParser::parseCaller($this->request));

        return ['content' => JsonApi::renderChart($this->{'make' . $this->type}())];
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

    public function setCaller(Caller $caller): void
    {
        $this->caller = $caller;
    }

    private function injectContextVariables(): void
    {
        $contextVariables = ContextVariablesInstantiator::buildContextVariables($this->caller, $this->request->get('contextVariables'));
        $rawFilter = $this->request->get('filter');
        array_walk_recursive($rawFilter, static function (&$value) use ($contextVariables) {
            $value = ContextVariablesInjector::injectContextInValue($value, $contextVariables);
        });
        $this->request->set('filter', $rawFilter);
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
            operation: $this->request->get('aggregator'),
            field: $this->request->get('aggregateFieldName'),
            groups: $this->request->get('groupByFieldName') ? [['field' => $this->request->get('groupByFieldName')]] : []
        );

        $result = $this->collection->aggregate($this->caller, $this->filter, $aggregation);

        return new PieChart($this->mapArrayToKeyValueAggregate($result));
    }

    private function makeLine(): LineChart
    {
        $aggregation = new Aggregation(
            operation: $this->request->get('aggregator'),
            field: $this->request->get('aggregateFieldName'),
            groups: [['field' => $this->request->get('groupByFieldName'), 'operation' => $this->request->get('timeRange')]]
        );

        $result = $this->collection->aggregate($this->caller, $this->filter, $aggregation);

        return new LineChart($this->mapArrayToLabelValue($result));
    }

    private function makeLeaderboard(): LeaderboardChart
    {
        /** @var RelationSchema $field */
        $field = $this->collection->getFields()[$this->request->get('relationshipFieldName')];
        $foreignCollectionName = null;

        if ($field->getType() === 'OneToMany') {
            $foreignCollectionName = CollectionUtils::getInverseRelation($this->collection, $this->request->get('relationshipFieldName'));
        }

        if ($field->getType() === 'ManyToMany') {
            $foreignCollectionName = CollectionUtils::getThroughTarget($this->collection, $this->request->get('relationshipFieldName'));
        }

        $aggregation = new Aggregation(
            operation: $this->request->get('aggregator'),
            field: $this->request->get('aggregateFieldName'),
            groups: $this->request->get('labelFieldName') ? [['field' => $this->request->get('labelFieldName')]] : []
        );

        if (! $foreignCollectionName || ! $aggregation->getGroups()) {
            throw new ForestException('Failed to generate leaderboard chart: parameters do not match pre-requisites');
        }
        $filter = $this->filter->nest($foreignCollectionName);
        $result = $this->collection->aggregate($this->caller, $filter, $aggregation, $this->request->get('limit'));

        return new LeaderboardChart($this->mapArrayToKeyValueAggregate($result));
    }

    private function computeValue(Filter $filter): int
    {
        $aggregation = new Aggregation(operation: $this->request->get('aggregator'), field: $this->request->get('aggregateFieldName'));
        $result = $this->collection->aggregate($this->caller, $filter, $aggregation);

        return $result[0]['value'] ?? 0;
    }

    private function mapArrayToKeyValueAggregate($array): array
    {
        return collect($array)
            ->map(function ($item) {
                // @codeCoverageIgnoreStart
                $key = array_shift($item['group']);
                $value = $item['value'];
                // @codeCoverageIgnoreEnd

                return compact('key', 'value');
            })->toArray();
    }

    private function mapArrayToLabelValue($array): array
    {
        return collect($array)
            ->map(function ($item) {
                return [
                    'label'  => Carbon::parse(array_shift($item['group']))
                        ->format($this->getFormat()),
                    'values' => ['value' => $item['value']],
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * @return string
     */
    private function getFormat(): string
    {
        if (! $this->request->get('timeRange')) {
            throw new ForestException("The parameter timeRange is not defined");
        }

        return match (Str::lower($this->request->get('timeRange'))) {
            'day'   => 'd/m/Y',
            'week'  => '\WW-Y',
            'month' => 'M Y',
            'year'  => 'Y',
            default => '',
        };
    }
}
