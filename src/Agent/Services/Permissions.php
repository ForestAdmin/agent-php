<?php

namespace ForestAdmin\AgentPHP\Agent\Services;

use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Utils\ForestHttpApi;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTree;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection as IlluminateCollection;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\Types\Boolean;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Permissions
{
    public const TTL = 60 * 60 * 24;

    public function __construct(protected Caller $caller)
    {
    }

    public function getCacheKey(int $renderingId): string
    {
        return "permissions.$renderingId";
    }

    public function invalidateCache(int $renderingId): void
    {
        Cache::forget($this->getCacheKey($renderingId));
    }

    public function canChart(Request $request, $allowFetch = true): bool
    {
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
        $isAllowed = $permissions->get('charts')?->contains("$type:$chartHash");

        if (! $isAllowed && $allowFetch) {
            $this->invalidateCache($this->caller->getRenderingId());

            return $this->canChart($request, false);
        }

        if (! $isAllowed) {
            throw new HttpException(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        return $isAllowed;
    }

    public function can(string $action, $allowFetch = true): bool
    {
        $permissions = $this->getRenderingPermissions($this->caller->getRenderingId());
        $isAllowed = $permissions->get('actions')?->get($action)?->contains($this->caller->getId());

        if (! $isAllowed && $allowFetch) {
            $this->invalidateCache($this->caller->getRenderingId());

            return $this->can($action, false);
        }

        if (! $isAllowed) {
            throw new HttpException(Response::HTTP_FORBIDDEN, 'Forbidden');
        }

        return $isAllowed;
    }

    public function getScope(Collection $collection): ?ConditionTree
    {
        $permissions = $this->getRenderingPermissions($this->caller->getRenderingId());
        $scopes = $permissions->get('scopes')->get($collection->getName());

        if (! $scopes) {
            return null;
        }

        return $scopes['conditionTree']->replaceLeafs(
            function (ConditionTreeLeaf $leaf) use ($scopes) {
                $dynamicValues = Arr::get($scopes, 'dynamicScopeValues.' . $this->caller->getId());

                if (is_string($leaf->getValue()) && Str::startsWith($leaf->getValue(), '$currentUser')) {
                    if ($dynamicValues) {
                        return $leaf->override(value: $dynamicValues[$leaf->getValue()]);
                    }

                    return $leaf->override(value: Str::startsWith($leaf->getValue(), '$currentUser.tags.')
                        ? $this->caller->getTag(Str::substr($leaf->getValue(), 18))
                        : $this->caller->getValue(Str::substr($leaf->getValue(), 13)));
                }

                return $leaf;
            }
        );
    }

    protected function getRenderingPermissions(int $renderingId): IlluminateCollection
    {
        return Cache::remember(
            $this->getCacheKey($renderingId),
            function () use ($renderingId) {
                $permissions = ForestHttpApi::getPermissions($renderingId);

                return collect(
                    [
                        'actions' => $this->decodeActionPermissions($permissions),
                        'scopes'  => $this->decodeScopePermissions($permissions, $renderingId),
                        'charts'  => $this->decodeChartPermissions($permissions)
                    ]
                );
            },
            self::TTL
        );
    }

    private function decodeActionPermissions(array $rawPermissions): IlluminateCollection
    {
        $actions = collect();
        foreach ($rawPermissions['data']['collections'] as $collectionName => $permission) {
            foreach ($permission['collection'] as $actionName              => $userIds) {
                $shortName = Str::before($actionName, 'Enabled');
                $userIds = $userIds instanceof Boolean ? [$this->caller->getId()] : $userIds;
                $actions->put("$shortName:$collectionName", collect($userIds));
            }

            foreach ($permission['actions'] as $actionName => $actionPermissions) {
                $userIds = $actionPermissions['triggerEnabled'];
                $userIds = $userIds instanceof Boolean ? [$this->caller->getId()] : $userIds;

                $actions->put("custom:$actionName:$collectionName", collect($userIds));
            }
        }

        return $actions;
    }

    private function decodeScopePermissions(array $rawPermissions, int $renderingId): IlluminateCollection
    {
        $scopes = [];
        if (isset($rawPermissions['data']['renderings'][$renderingId])) {
            foreach ($rawPermissions['data']['renderings'][$renderingId] as $collectionName => $value) {
                if (isset($value['scope'])) {
                    $scopes[$collectionName] = [
                        'conditionTree'      => ConditionTreeFactory::fromArray($value['scope']['filter']),
                        'dynamicScopeValues' => $value['scope']['dynamicScopesValues']['users'] ?? [],
                    ];
                }
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

    private function arrayHash(array $data): string
    {
        return sha1(json_encode(ksort($data), JSON_THROW_ON_ERROR));
    }
}
