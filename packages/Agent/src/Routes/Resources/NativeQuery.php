<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources;

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Facades\JsonApi;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractAuthenticatedRoute;
use ForestAdmin\AgentPHP\Agent\Utils\ContextVariablesInjector;
use ForestAdmin\AgentPHP\Agent\Utils\ContextVariablesInstantiator;
use ForestAdmin\AgentPHP\Agent\Utils\QueryValidator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\LeaderboardChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\LineChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\ObjectiveChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\PieChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\ValueChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

class NativeQuery extends AbstractAuthenticatedRoute
{
    protected string $type;

    /**
     * @return $this
     */
    public function setupRoutes(): self
    {
        $this->addRoute(
            'forest.native_query',
            'post',
            '/_internal/native_query',
            fn ($args) => $this->handleRequest($args)
        );

        return $this;
    }

    public function handleRequest(array $args = []): array
    {
        $this->build($args);
        QueryValidator::valid($this->request->get('query'));
        //        $this->permissions->canChart($this->request);

        $this->setType($this->request->get('type'));
        [$query, $contextVariables] = $this->injectContextVariables();
        $query = str_replace('?', $this->request->get('record_id'), $query);

        /** @var Datasource $rootDatasource */
        $rootDatasource = AgentFactory::getInstance()->getCustomizer()->getRootDatasourceByConnection($this->request->get('connectionName'));
        $result = $this->convertStdClassToArray(
            $rootDatasource->executeNativeQuery($this->request->get('connectionName'), $query, $contextVariables)
        );

        return ['content' => JsonApi::renderChart($this->{'make' . $this->type}($result))];
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

    private function injectContextVariables(): array
    {
        $contextVariables = ContextVariablesInstantiator::buildContextVariables($this->caller, $this->request->get('contextVariables') ?? []);

        return ContextVariablesInjector::injectContextInNativeQuery($this->request->get('query'), $contextVariables);
    }

    private function makeValue(array $result): ValueChart
    {
        $result = empty($result) ? [] : $result[0];

        if (! isset($result['value'])) {
            throw new ForestException("The key 'value' is not present in the result");
        }

        return new ValueChart(...$result);
    }

    private function makeObjective(array $result): ObjectiveChart
    {
        $result = empty($result) ? [] : $result[0];

        if (! isset($result['value']) || ! isset($result['objective'])) {
            throw new ForestException("The keys 'value' and 'objective' are not present in the result");
        }

        return new ObjectiveChart(...$result);
    }

    private function makePie(array $result): PieChart
    {
        $result = empty($result) ? [] : $result;

        foreach ($result as $item) {
            if (! array_key_exists('key', $item) || ! array_key_exists('value', $item)) {
                throw new ForestException("The keys 'key' and 'value' are not present in the result");
            }
        }

        return new PieChart($result);
    }

    private function makeLine(array $result): LineChart
    {
        $result = empty($result) ? [] : $result;

        $lines = array_map(function ($resultLine) {
            if (! array_key_exists('key', $resultLine) || ! array_key_exists('value', $resultLine)) {
                throw new ForestException("The keys 'key' and 'value' are not present in the result");
            }

            return [
                'label'  => $resultLine['key'],
                'values' => ['value' => $resultLine['value']],
            ];
        }, $result);

        return new LineChart($lines);
    }

    private function makeLeaderboard(array $result): LeaderboardChart
    {
        $result = empty($result) ? [] : $result;

        foreach ($result as $item) {
            if (! array_key_exists('key', $item) || ! array_key_exists('value', $item)) {
                throw new ForestException("The keys 'key' and 'value' are not present in the result");
            }
        }

        return new LeaderboardChart($result);
    }

    private function convertStdClassToArray(array $input): array
    {
        return array_map(fn ($item) => (array) $item, $input);
    }
}
