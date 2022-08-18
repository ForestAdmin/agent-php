<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Charts;

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Services\ForestAdminHttpDriverServices;
use ForestAdmin\AgentPHP\Agent\Utils\ContextFilterFactory;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\ObjectiveChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\PieChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\ValueChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\FilterFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Charts extends AbstractRoute
{
    protected Collection $collection;

    protected Filter $filter;

    protected Request $request;

    protected string $type;

    protected Caller $caller;

    public function __construct(
        ForestAdminHttpDriverServices $services,
    ) {
        parent::__construct($services);
    }

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
        $datasource = AgentFactory::get('datasource');
        $this->collection = $datasource->getCollection($args['collectionName']);
        $this->collection->hydrate($args);
        $this->request = Request::createFromGlobals();
        $scope = null; // todo
        $this->filter = ContextFilterFactory::build($this->collection, $this->request, $scope);

        $this->setType($this->request->get('type'));
        $this->setCaller(QueryStringParser::parseCaller($this->request));

        return [
            'renderChart' => true,
            'content'     => $this->{'make' . $this->type}(),
        ];
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

    private function makeValue(): ValueChart
    {
        $result = [
            'value'         => $this->computeValue($this->filter),
            'previousValue' => null,
        ];

        $isAndAggregator = $this->filter->getConditionTree() instanceof ConditionTreeBranch && $this->filter->getConditionTree()->getAggregator() === 'And';
        $withCountPrevious = (bool)$this->filter->getConditionTree()?->someLeaf(fn ($leaf) => $leaf->useIntervalOperator());

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

        $result = $this->collection->aggregate($this->type, $this->caller, $this->filter, $aggregation);

        return new PieChart($this->mapArrayToKeyValueAggregate($result, $aggregate));
    }

    private function computeValue(Filter $filter): int
    {
        $aggregation = new Aggregation(operation: $this->request->get('aggregate'), field: $this->request->get('aggregate_field'));
        $result = $this->collection->aggregate($this->type, $this->caller, $filter, $aggregation);
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
}
