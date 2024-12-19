<?php

namespace ForestAdmin\AgentPHP\Agent\Utils\Traits;

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Services\Permissions;
use ForestAdmin\AgentPHP\Agent\Utils\ContextVariables;
use ForestAdmin\AgentPHP\Agent\Utils\ContextVariablesInjector;
use ForestAdmin\AgentPHP\Agent\Utils\QueryValidator;
use ForestAdmin\AgentPHP\DatasourceCustomizer\DatasourceCustomizer;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTree;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\ConditionTreeValidator;

trait QueryHandler
{
    protected function executeQuery(string $query, string $connectionName, Permissions $permissions, Caller $caller, ?array $requestContextVariables = []): array
    {
        $query = preg_replace('/\s+/', ' ', trim($query));
        [$query, $contextVariables] = $this->injectContextVariables($query, $caller, $permissions, $requestContextVariables);

        /** @var DatasourceCustomizer $customizer */
        $customizer = AgentFactory::getInstance()->getCustomizer();
        $rootDatasource = $customizer->getRootDatasourceByConnection($connectionName);

        return $rootDatasource->executeNativeQuery($connectionName, $query, $contextVariables);
    }

    protected function injectContextVariables(string $query, Caller $caller, Permissions $permissions, ?array $requestContextVariables = []): array
    {
        $user = $permissions->getUserData($caller->getId());
        $team = $permissions->getTeam($caller->getRenderingId());
        $contextVariables = new ContextVariables($team, $user, $requestContextVariables);

        return ContextVariablesInjector::injectContextInNativeQuery(
            $query,
            $contextVariables
        );
    }

    protected function parseQuerySegment(CollectionContract $collection, Permissions $permissions, Caller $caller): ?ConditionTree
    {
        if (! $this->request->get('segmentQuery')) {
            return null;
        }

        if (! $this->request->get('connectionName')) {
            throw new ForestException("'connectionName' parameter is mandatory");
        }

        QueryValidator::valid($this->request->get('segmentQuery'));
        $permissions->canExecuteQuerySegment($collection, $this->request->get('segmentQuery'), $this->request->get('connectionName'));

        $result = $this->convertStdClassToArray(
            $this->executeQuery(
                $this->request->get('segmentQuery'),
                $this->request->get('connectionName'),
                $permissions,
                $caller,
                $this->request->get('contextVariables')
            )
        );
        $ids = array_map(fn ($row) => array_values($row), $result);
        $conditionTreeSegment = ConditionTreeFactory::matchIds($collection, $ids);
        ConditionTreeValidator::validate($conditionTreeSegment, $collection);

        return $conditionTreeSegment;
    }

    private function convertStdClassToArray(array $input): array
    {
        return array_map(fn ($item) => (array) $item, $input);
    }
}
