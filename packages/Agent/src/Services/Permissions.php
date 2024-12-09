<?php

namespace ForestAdmin\AgentPHP\Agent\Services;

use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Facades\ForestSchema;
use ForestAdmin\AgentPHP\Agent\Facades\Logger;
use ForestAdmin\AgentPHP\Agent\Http\Exceptions\ForbiddenError;
use ForestAdmin\AgentPHP\Agent\Http\ForestApiRequester;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Utils\ArrayHelper;
use ForestAdmin\AgentPHP\Agent\Utils\ContextVariables;
use ForestAdmin\AgentPHP\Agent\Utils\ContextVariablesInjector;
use ForestAdmin\AgentPHP\Agent\Utils\ForestHttpApi;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTree;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;

use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

use function ForestAdmin\config;

use Illuminate\Support\Collection as IlluminateCollection;

class Permissions
{
    private ForestApiRequester $forestApi;

    public function __construct(protected Caller $caller)
    {
        $this->forestApi = new ForestApiRequester();
    }

    public function invalidateCache(string $idCache): void
    {
        Cache::forget($idCache);

        Logger::log('Debug', "Invalidating $idCache cache.");
    }

    public function can(string $action, CollectionContract $collection, $allowFetch = false): bool
    {
        if (! $this->hasPermissionSystem()) {
            return true;
        }

        $userData = $this->getUserData($this->caller->getId());
        $collectionsData = $this->getCollectionsPermissionsData($allowFetch);

        $isAllowed = array_key_exists($collection->getName(), $collectionsData) && in_array($userData['roleId'], $collectionsData[$collection->getName()][$action], true);

        // Refetch
        if (! $isAllowed) {
            $collectionsData = $this->getCollectionsPermissionsData(true);
            $isAllowed = in_array($userData['roleId'], $collectionsData[$collection->getName()][$action], true);
        }

        // still not allowed - throw forbidden message
        if (! $isAllowed) {
            throw new ForbiddenError('You don\'t have permission to ' . $action . ' this collection.');
        }

        return $isAllowed;
    }

    public function canChart(Request $request): bool
    {
        $attributes = $request->all();
        unset($attributes['timezone'], $attributes['collection'], $attributes['contextVariables'], $attributes['record_id']);
        $attributes = array_filter($attributes, static fn ($value) => ! is_null($value) && $value !== '');
        $hashRequest = $attributes['type'] . ':' . $this->arrayHash($attributes);
        $isAllowed = in_array($hashRequest, $this->getChartData($this->caller->getRenderingId()), true);

        // Refetch
        if (! $isAllowed) {
            $isAllowed = in_array($hashRequest, $this->getChartData($this->caller->getRenderingId(), true), true);
        }

        // still not allowed - throw forbidden message
        if (! $isAllowed) {
            Logger::log('Debug', 'User ' . $this->caller->getId() .' cannot retrieve chart on rendering ' . $this->caller->getRenderingId());

            throw new ForbiddenError('You don\'t have permission to access this collection.');
        }

        Logger::log('Debug', 'User ' . $this->caller->getId() .' can retrieve chart on rendering ' . $this->caller->getRenderingId());

        return $isAllowed;
    }

    public function canSmartAction(Request $request, CollectionContract $collection, Filter $filter, $allowFetch = true): bool
    {
        if (! $this->hasPermissionSystem()) {
            return true;
        }

        $userData = $this->getUserData($this->caller->getId());
        $collectionsData = $this->getCollectionsPermissionsData($allowFetch);
        $action = $this->findActionFromEndpoint($collection->getName(), $request->getPathInfo(), $request->getMethod());

        if (null === $action) {
            throw new ForestException('The collection ' . $collection->getName() . ' does not have this smart action');
        }

        $smartActionApproval = new SmartActionChecker(
            $request,
            $collection,
            $collectionsData[$collection->getName()]['actions'][$action['name']],
            $this->caller,
            $userData['roleId'],
            $filter
        );

        $isAllowed = $smartActionApproval->canExecute();
        Logger::log('Debug', 'User ' . $userData['roleId'] . ' is ' . $isAllowed ? '' : 'not' . ' allowed to perform ' . $action['name']);

        return $isAllowed;
    }

    public function canExecuteQuerySegment(CollectionContract $collection, string $query, string $connectionName): bool
    {
        $hashRequest = $this->arrayHash(['query' => $query, 'connectionName' => $connectionName]);
        $isAllowed = in_array($hashRequest, $this->getSegments($collection), true);

        // Refetch
        if (! $isAllowed) {
            $isAllowed = in_array($hashRequest, $this->getSegments($collection, true), true);
        }

        // Still not allowed - throw forbidden message
        if (! $isAllowed) {
            Logger::log(
                'Debug',
                sprintf(
                    "User %s cannot retrieve query segment on rendering %s",
                    $this->caller->getId(),
                    $this->caller->getRenderingId()
                )
            );

            throw new ForbiddenError("You don't have permission to use this query segment.");
        }

        Logger::log(
            'Debug',
            sprintf(
                "User %s can retrieve query segment on rendering %s",
                $this->caller->getId(),
                $this->caller->getRenderingId()
            )
        );

        return $isAllowed;
    }

    public function getScope(CollectionContract $collection): ?ConditionTree
    {
        $permissions = $this->getRenderingData($this->caller->getRenderingId());

        if (! in_array($collection->getName(), array_keys($permissions->get('scopes')), true)) {
            return null;
        }

        $scope = $permissions->get('scopes')[$collection->getName()];
        $team = $permissions->get('team');
        $user = $this->getUserData($this->caller->getId());
        $contextVariables = new ContextVariables(team: $team, user: $user);

        return ContextVariablesInjector::injectContextInFilter($scope, $contextVariables);
    }

    public function getUserData(int $userId): array
    {
        $cache = Cache::remember(
            'forest.users',
            function () {
                $response = $this->fetch('/liana/v4/permissions/users');
                $users = [];
                foreach ($response as $user) {
                    $users[$user['id']] = $user;
                }

                Logger::log('Debug', 'Refreshing user permissions cache');

                return $users;
            },
            config('permissionExpiration')
        );

        return $cache[$userId];
    }

    public function getSegments(CollectionContract $collection, bool $forceFetch = false): array
    {
        $permissions = $this->getRenderingData($this->caller->getRenderingId(), $forceFetch);

        return $permissions->get('segments')[$collection->getName()];
    }

    public function getTeam(int $renderingId): array
    {
        $permissions = $this->getRenderingData($renderingId);

        return $permissions->get('team');
    }

    protected function getCollectionsPermissionsData(bool $forceFetch = false): array
    {
        if ($forceFetch) {
            $this->invalidateCache('forest.collections');
        }

        return Cache::remember(
            'forest.collections',
            function () {
                $response = $this->fetch('/liana/v4/permissions/environment');
                $collections = [];
                foreach ($response['collections'] as $name => $collection) {
                    $collections[$name] = array_merge($this->decodeCrudPermissions($collection), $this->decodeActionPermissions($collection));
                }

                Logger::log('Debug', 'Fetching environment permissions');

                return $collections;
            },
            config('permissionExpiration')
        );
    }

    protected function getChartData(int $renderingId, $forceFetch = false): array
    {
        $renderingData = $this->getRenderingData($renderingId, $forceFetch);

        return $renderingData->get('charts');
    }

    protected function arrayHash(array $data): string
    {
        ArrayHelper::ksortRecursive($data);

        return sha1(json_encode($data, JSON_THROW_ON_ERROR));
    }

    protected function getRenderingData(int $renderingId, bool $forceFetch = false): IlluminateCollection
    {
        if ($forceFetch) {
            $this->invalidateCache('forest.rendering');
        }

        return Cache::remember(
            'forest.rendering',
            function () use ($renderingId) {
                $data = collect();
                $response = $this->fetch('/liana/v4/permissions/renderings/' . $renderingId);

                $data->put('scopes', $this->decodeScopePermissions($response['collections']));
                $data->put('team', $response['team']);
                $data->put('segments', $this->decodeSegmentPermissions($response['collections']));
                $data->put('charts', $this->decodeChartPermissions($response['stats']));

                return $data;
            },
            config('permissionExpiration')
        );
    }

    protected function hasPermissionSystem()
    {
        return Cache::remember(
            'forest.has_permission',
            function () {
                $response = $this->fetch('/liana/v4/permissions/environment');

                return ! ($response === true);
            },
            config('permissionExpiration')
        );
    }

    protected function findActionFromEndpoint($collectionName, $endpoint, $httpMethod): ?array
    {
        $actions = ForestSchema::getSmartActions($collectionName);

        if (empty($actions)) {
            return null;
        }

        $action = collect($actions)
            ->where('endpoint', $endpoint)
            ->where('httpMethod', $httpMethod)
            ->first();

        return $action;
    }

    protected function decodeCrudPermissions(array $collection): array
    {
        return [
            'browse'  => $collection['collection']['browseEnabled']['roles'],
            'read'    => $collection['collection']['readEnabled']['roles'],
            'edit'    => $collection['collection']['editEnabled']['roles'],
            'add'     => $collection['collection']['addEnabled']['roles'],
            'delete'  => $collection['collection']['deleteEnabled']['roles'],
            'export'  => $collection['collection']['exportEnabled']['roles'],
        ];
    }

    protected function decodeActionPermissions(array $collection): array
    {
        $actions = ['actions' => []];
        foreach ($collection['actions'] as $id => $action) {
            $actions['actions'][$id] = [
                'triggerEnabled'              => $action['triggerEnabled']['roles'],
                'triggerConditions'           => $action['triggerConditions'],
                'approvalRequired'            => $action['approvalRequired']['roles'],
                'approvalRequiredConditions'  => $action['approvalRequiredConditions'],
                'userApprovalEnabled'         => $action['userApprovalEnabled']['roles'],
                'userApprovalConditions'      => $action['userApprovalConditions'],
                'selfApprovalEnabled'         => $action['selfApprovalEnabled']['roles'],
            ];
        }

        return $actions;
    }

    protected function decodeScopePermissions(array $rawPermissions): array
    {
        $scopes = [];
        foreach ($rawPermissions as $collectionName => $value) {
            if (null !== $value['scope']) {
                $scopes[$collectionName] = ConditionTreeFactory::fromArray($value['scope']);
            }
        }

        return $scopes;
    }

    protected function decodeChartPermissions(array $rawPermissions): array
    {
        $charts = [];
        foreach ($rawPermissions as $chart) {
            $chart = array_filter($chart, static fn ($value) => ! is_null($value) && $value !== '');
            $charts[] = $chart['type'] . ':' . $this->arrayHash($chart);
        }

        return $charts;
    }

    protected function decodeSegmentPermissions(array $rawPermissions): array
    {
        $segments = [];
        foreach ($rawPermissions as $collectionName => $value) {
            $segments[$collectionName] = array_map(fn ($segment) => $this->arrayHash($segment), $value['liveQuerySegments']);
        }

        return $segments;
    }

    protected function fetch(string $url)
    {
        try {
            $response = $this->forestApi->get($url);

            return json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            // @codeCoverageIgnoreStart
        } catch (\Exception $e) {
            ForestHttpApi::handleResponseError($e);
        }
        // @codeCoverageIgnoreEnd
    }
}
