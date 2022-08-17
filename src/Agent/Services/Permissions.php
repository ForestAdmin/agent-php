<?php

namespace ForestAdmin\AgentPHP\Agent\Services;

use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Utils\ForestHttpApi;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;

use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use Illuminate\Support\Collection as IlluminateCollection;
use Symfony\Component\HttpFoundation\Response;
use function ForestAdmin\cache;

use Illuminate\Support\Str;
use phpDocumentor\Reflection\Types\Boolean;

class Permissions
{
    public const TTL = 60 * 60 * 24;

    public function __construct(protected Caller $caller)
    {
    }

    public function invalidateCache(int $renderingId): void
    {
        Cache::forget("permissions.$renderingId");
    }

    public function canChart(): void
    {
        // todo Checks that a charting query is in the list of allowed queries
    }

    public function can(string $action, string $collectionName, $allowFetch = true): bool
    {
        $this->invalidateCache($this->caller->getRenderingId());
        $permissions = $this->getRenderingPermissions($this->caller->getRenderingId());
        $isAllowed = $permissions->get('actions')->get($action)->contains($this->caller->getId());

        if (! $isAllowed && $allowFetch) {
            $this->invalidateCache($this->caller->getRenderingId());

            return $this->can($action, $collectionName, false);
        }

        if (! $isAllowed) {
            throw new ForestException('Forbidden', Response::HTTP_FORBIDDEN);
        }

        return $isAllowed;
    }

    public function getScope(): void
    {
        // todo
    }

    private function getRenderingPermissions(int $renderingId): IlluminateCollection
    {
        return Cache::remember(
            "permissions.$renderingId",
            function () use ($renderingId) {
                $permissions = ForestHttpApi::getPermissions($renderingId);

                // todo decode Chart
                return collect(
                    [
                        'actions' => $this->decodeActionPermissions($permissions),
                        'scopes'  => $this->decodeScopePermissions($permissions, $renderingId),
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
            foreach ($permission['collection'] as $actionName => $userIds) {
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
}
