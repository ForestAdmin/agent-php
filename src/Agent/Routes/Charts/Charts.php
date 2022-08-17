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
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\ValueChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\FilterFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

class Charts extends AbstractRoute
{
    protected Collection $collection;

    protected Filter $filter;

    protected Request $request;

    protected string $type;

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

        return [
            'renderChart' => true,
            'content'     => $this->{'make' . $this->type}(),
        ];
    }

    /**
     * @param string $type
     */
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
        $caller = QueryStringParser::parseCaller($this->request);
        $result = [
            'value'         => $this->computeValue($this->request, $this->filter, $caller),
            'previousValue' => null,
        ];

        $isAndAggregator = $this->filter->getConditionTree() instanceof ConditionTreeBranch && $this->filter->getConditionTree()->getAggregator() === 'And';
        $withCountPrevious = (bool)$this->filter->getConditionTree()?->someLeaf(fn ($leaf) => $leaf->useIntervalOperator());

        if ($withCountPrevious && ! $isAndAggregator) {
            $result['previousValue'] = $this->computeValue($this->request, FilterFactory::getPreviousPeriodFilter($this->filter, $caller->getTimezone()), $caller);
        }

        return new ValueChart(...$result);
    }

    private function computeValue(Request $request, Filter $filter, Caller $caller): int
    {
        $aggregation = new Aggregation(operation: $request->get('aggregate'), field: $request->get('aggregate_field')); //  groups: [['field' => 'reference','operation' => 'Date' ]]
        $rows = $this->collection->aggregate($this->type, $caller, $filter, $aggregation);

        return $rows ?? 0;
    }
}
