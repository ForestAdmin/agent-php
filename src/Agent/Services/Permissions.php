<?php

namespace ForestAdmin\AgentPHP\Agent\Services;

use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Facades\ForestSchema;
use ForestAdmin\AgentPHP\Agent\Http\Exceptions\ForbiddenError;
use ForestAdmin\AgentPHP\Agent\Http\ForestApiRequester;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Utils\ContextVariables;
use ForestAdmin\AgentPHP\Agent\Utils\ContextVariablesInjector;
use ForestAdmin\AgentPHP\Agent\Utils\ForestHttpApi;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTree;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;

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
    }

    public function can(string $action, CollectionContract $collection, $allowFetch = false): bool
    {
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
        unset($attributes['timezone']);
        unset($attributes['collection']);
        unset($attributes['contextVariables']);

        $attributes = array_filter($attributes, static fn ($value) => ! is_null($value) && $value !== '');
        $hashRequest = $attributes['type'] . ':' . $this->arrayHash($attributes);
        $isAllowed = in_array($hashRequest, $this->getChartData($this->caller->getRenderingId()), true);

        // Refetch
        if (! $isAllowed) {
            $isAllowed = in_array($hashRequest, $this->getChartData($this->caller->getRenderingId(), true), true);
        }

        // still not allowed - throw forbidden message
        if (! $isAllowed) {
            throw new ForbiddenError('You don\'t have permission to access this collection.');
        }

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
        $smartActionApproval = new SmartActionChecker(
            $request,
            $collection,
            $collectionsData[$collection->getName()]['actions'][$action['name']],
            $this->caller,
            $userData['roleId'],
            $filter
        );

        return $smartActionApproval->canExecute();
    }

    public function getScope(CollectionContract $collection): ?ConditionTree
    {
        $permissions = $this->getScopeAndTeamData($this->caller->getRenderingId());

        $scope = $permissions->get('scopes')->get($collection->getName());
        $team = $permissions->get('team');
        $user = $this->getUserData($this->caller->getId());

        if (! $scope) {
            return null;
        }

        $contextVariables = new ContextVariables(team: $team, user: $user);

        return ContextVariablesInjector::injectContextInFilter($scope, $contextVariables);
    }

    protected function getUserData(int $userId): array
    {
        $cache = Cache::remember(
            'forest.users',
            function () {
                $response = $this->fetch('/liana/v4/permissions/users');
                $users = [];
                foreach ($response as $user) {
                    $users[$user['id']] = $user;
                }

                return $users;
            },
            config('permissionExpiration')
        );

        return $cache[$userId];
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

                return $collections;
            },
            config('permissionExpiration')
        );
    }

    protected function getChartData(int $renderingId, $forceFetch = false): array
    {
        if ($forceFetch) {
            $this->invalidateCache('forest.stats');
        }

        return Cache::remember(
            'forest.stats',
            function () use ($renderingId) {
                $response = $this->fetch('/liana/v4/permissions/renderings/' . $renderingId);
                $statHash = [];
                foreach ($response['stats'] as $stat) {
                    $stat = array_filter($stat, static fn ($value) => ! is_null($value) && $value !== '');
                    $statHash[] = $stat['type'] . ':' . $this->arrayHash($stat);
                }

                return $statHash;
            },
            config('permissionExpiration')
        );
    }

    protected function arrayHash(array $data): string
    {
        ksort($data);

        return sha1(json_encode($data, JSON_THROW_ON_ERROR));
    }

    protected function getScopeAndTeamData(int $renderingId, $forceFetch = false): IlluminateCollection
    {
        if ($forceFetch) {
            $this->invalidateCache('forest.scopes');
        }

        return Cache::remember(
            'forest.scopes',
            function () use ($renderingId) {
                $data = collect();
                $response = $this->fetch('/liana/v4/permissions/renderings/' . $renderingId);

                $data->put('scopes', $this->decodeScopePermissions($response['collections']));
                $data->put('team', $response['team']);

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

    protected function decodeScopePermissions(array $rawPermissions): IlluminateCollection
    {
        $scopes = [];
        foreach ($rawPermissions as $collectionName => $value) {
            if (null !== $value['scope']) {
                $scopes[$collectionName] = ConditionTreeFactory::fromArray($value['scope']);
            }
        }

        return collect($scopes);
    }

    protected function fetch(string $url)
    {
        try {
            $response = $this->forestApi->get($url);

            return json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception $e) {
            ForestHttpApi::handleResponseError($e);
        }
    }
}
