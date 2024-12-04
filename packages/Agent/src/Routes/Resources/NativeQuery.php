<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources;

use ForestAdmin\AgentPHP\Agent\Facades\JsonApi;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractAuthenticatedRoute;
use ForestAdmin\AgentPHP\Agent\Utils\QueryValidator;
use ForestAdmin\AgentPHP\Agent\Utils\Traits\QueryHandler;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\LeaderboardChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\LineChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\ObjectiveChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\PieChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\ValueChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

class NativeQuery extends AbstractAuthenticatedRoute
{
    use QueryHandler;

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

        $this->setType($this->request->get('type'));
        $query = str_replace('?', $this->request->get('record_id'), $this->request->get('query'));

        $result = $this->convertStdClassToArray(
            $this->executeQuery(
                $query,
                $this->request->get('connectionName'),
                $this->permissions,
                $this->caller,
                $this->request->get('contextVariables')
            )
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
            if (! array_key_exists('label', $resultLine) || ! array_key_exists('values', $resultLine)) {
                throw new ForestException("The keys 'label' and 'values' are not present in the result");
            }

            return [
                'label'  => $resultLine['label'],
                'values' => $resultLine['values'],
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
}
