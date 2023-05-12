<?php

namespace ForestAdmin\AgentPHP\Agent\Services;

use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Http\ForestApiRequester;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Utils\ContextVariables;
use ForestAdmin\AgentPHP\Agent\Utils\ContextVariablesInjector;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTree;

use function ForestAdmin\config;

use Illuminate\Support\Collection as IlluminateCollection;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\Types\Boolean;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Permissions
{
    //public const TTL =1;// 60 * 60 * 24;

    public const ALLOWED_PERMISSION_LEVELS = ['admin', 'editor', 'developer'];

    private ForestApiRequester $forestApi;

    public function __construct(protected Caller $caller)
    {
        $this->forestApi = new ForestApiRequester();
    }

//    public function getCacheKey(int $renderingId): string
//    {
//        return "permissions.$renderingId";
//    }

    public function invalidateCache(string $idCache): void
    {
        Cache::forget($this->getCacheKey($idCache));
    }

    public function can(string $action, CollectionContract $collection, $allowFetch = false): bool
    {
        $userData = $this->getUserData($this->caller->getId());
        $collectionsData = $this->getCollectionsPermissionsData($allowFetch);

        try {
            $isAllowed = in_array($userData['roleId'], $collectionsData[$collection->getName()][$action], true);
            if (! $isAllowed) {
                $collectionsData = $this->getCollectionsPermissionsData(true);
                $isAllowed = in_array($userData['roleId'], $collectionsData[$collection->getName()][$action], true);
            }

            return $isAllowed;
        } catch (\Exception $e) {
            throw new HttpException(Response::HTTP_CONFLICT, 'The collection ' . $collection->getName() . ' doesn\'t exist');
        }
    }

    public function canChart(Request $request, $allowFetch = true): bool
    {
        // A REMPLACER PAR
        //$this->getChartData($this->caller->getRenderingId());


        $chart = $request->all();
        unset($chart['timezone']);
        $type = strtolower(Str::plural($request->get('type')));

        // When the server sends the data of the allowed charts, the target column is not specified
        // for relations => allow them all.
        if ($request->input('group_by_field')
            && Str::contains($request->input('group_by_field'), ':')
        ) {
            $chart['group_by_field'] = Str::before($chart['group_by_field'], ':');
        }

        $chartHash = $this->arrayHash($chart);
        $permissions = $this->getRenderingPermissions($this->caller->getRenderingId());
        $isAllowed = in_array($this->caller->getValue('permission_level'), self::ALLOWED_PERMISSION_LEVELS, true)
            || $permissions->get('charts')?->contains("$type:$chartHash");

        if (! $isAllowed && $allowFetch) {
            $this->invalidateCache($this->caller->getRenderingId());

            return $this->canChart($request, false);
        }

        if (! $isAllowed) {
            throw new HttpException(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        return $isAllowed;
    }

    public function canSmartAction(Request $request, CollectionContract $collection, $allowFetch = true): bool
    {
        dd($request->all(), $request->getPathInfo(), $request->getMethod());
        //Caller $caller, CollectionContract $collection, array $parameters, string $endpoint, string $httpMethod
        $actionAuthorize = new ActionAuthorize($this->caller, $collection, $request->all(), $request->getPathInfo(), $request->getMethod());

//        $permissions = $this->getRenderingPermissions($this->caller->getRenderingId());
//        $isAllowed = $permissions->get('actions')?->get($action)?->contains($this->caller->getId());
//
//        if (! $isAllowed && $allowFetch) {
//            $this->invalidateCache($this->caller->getRenderingId());
//
//            return $this->can($action, false);
//        }
//
//        if (! $isAllowed) {
//            throw new HttpException(Response::HTTP_FORBIDDEN, 'Forbidden');
//        }
//
//        return $isAllowed;
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

//    protected function getRenderingPermissions(int $renderingId): IlluminateCollection
//    {
//        return Cache::remember(
//            $this->getCacheKey($renderingId),
//            function () use ($renderingId) {
//                $permissions = $this->getPermissions($renderingId);
//
//                return collect(
//                    [
//                        //'actions' => $this->decodeActionPermissions($permissions),
//                        'scopes'  => $this->decodeScopePermissions($permissions, $renderingId),
//                        'charts'  => $this->decodeChartPermissions($permissions),
//                    ]
//                );
//            },
//            config('permissionExpiration')
//        );
//    }

    private function getUserData(int $userId): array
    {
        $cache = Cache::remember(
            'forest.users',
            function () use ($userId) {
                $response = $this->forestApi->get('/liana/v4/permissions/users');
                $users = [];
                foreach (json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR) as $user) {
                    $users[$userId] = $user;
                }

                return $users;
            },
            config('permissionExpiration')
        );

        return $cache[$userId];
    }

    private function getCollectionsPermissionsData(bool $forceFetch = false): array
    {
        if ($forceFetch) {
            $this->invalidateCache('forest.collections');
        }

        return Cache::remember(
            'forest.collections',
            function () {
                $response = $this->forestApi->get('/liana/v4/permissions/environment');
                $collections = [];
                foreach (json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR)['collections'] as $name => $collection) {
                    $collections[$name] = array_merge($this->decodeCrudPermissions($collection), $this->decodeActionPermissions($collection));
                }

                return $collections;
            },
            config('permissionExpiration')
        );
    }

    private function getChartData(int $renderingId, $forceFetch = false): array
    {
        if ($forceFetch) {
            $this->invalidateCache('forest.stats');
        }

        return Cache::remember(
            'forest.stats',
            function () use ($renderingId) {
                $response = $this->forestApi->get('/liana/v4/permissions/renderings/' . $renderingId);
                $statHash = [];
                foreach (json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR)['stats'] as $stat) {
                    $statHash[] = $stat['type'] . ':' . $this->arrayHash($stat);
                }

                return $statHash;
            },
            config('permissionExpiration')
        );
    }

    private function getScopeAndTeamData(int $renderingId, $forceFetch = false): IlluminateCollection
    {
        if ($forceFetch) {
            $this->invalidateCache('forest.scopes');
        }

        return Cache::remember(
            'forest.scopes',
            function () use ($renderingId) {
                $data = collect();
                $response = $this->forestApi->get('/liana/v4/permissions/renderings/' . $renderingId);
                $body = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);

                $data->put('scopes', $this->decodeScopePermissions($body['collections']));
                $data->put('team', $body['team']);

                return $data;
            },
            config('permissionExpiration')
        );
    }

    private function decodeCrudPermissions(array $collection): array
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

    private function decodeActionPermissions(array $collection): array
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

//    private function decodeActionPermissions(array $rawPermissions): IlluminateCollection
//    {
//        $actions = collect();
//        foreach ($rawPermissions['data']['collections'] as $collectionName => $permission) {
//            foreach ($permission['collection'] as $actionName              => $userIds) {
//                $shortName = Str::before($actionName, 'Enabled');
//                $userIds = $userIds instanceof Boolean ? [$this->caller->getId()] : $userIds;
//                $actions->put("$shortName:$collectionName", collect($userIds));
//            }
//
//            foreach ($permission['actions'] as $actionName => $actionPermissions) {
//                $userIds = $actionPermissions['triggerEnabled'];
//                $userIds = $userIds instanceof Boolean ? [$this->caller->getId()] : $userIds;
//
//                $actions->put("custom:$actionName:$collectionName", collect($userIds));
//            }
//        }
//
//        return $actions;
//    }

    private function decodeScopePermissions(array $rawPermissions): IlluminateCollection
    {
        $scopes = [];
        foreach ($rawPermissions as $collectionName => $value) {
            if (null !== $value['scope']) {
                $scopes[$collectionName] = ConditionTreeFactory::fromArray($value['scope']);
            }
        }

        return collect($scopes);
    }

    private function decodeChartPermissions(array $rawPermissions): IlluminateCollection
    {
        $actions = collect();
        foreach ($rawPermissions['stats'] as $typeChart => $permissions) {
            foreach ($permissions as $permission) {
                $permission = array_filter($permission, static fn ($value) => ! is_null($value) && $value !== '');
                $permissionHash = $this->arrayHash($permission);
                $actions->push("$typeChart:$permissionHash");
            }
        }

        return $actions;
    }

    /**
     * @throws \JsonException
     */
    private function arrayHash(array $data): string
    {
        ksort($data);

        return sha1(json_encode($data, JSON_THROW_ON_ERROR));
    }
}
