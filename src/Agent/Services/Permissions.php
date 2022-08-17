<?php

namespace ForestAdmin\AgentPHP\Agent\Services;

use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Utils\ForestHttpApi;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;

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
        // todo
        //app('cache')->delete(....)
    }

    public function canChart(): void
    {
        // todo Checks that a charting query is in the list of allowed queries
    }

    public function can(): void
    {
        // todo Check if a user is allowed to perform a specific action
    }

    public function getScope(): void
    {
        // todo
    }

    private function getRenderingPermissions(int $renderingId): array
    {
        return Cache::remember(
            "permissions.$renderingId",
            function () use ($renderingId) {
                $permissions = ForestHttpApi::getPermissions($renderingId);

                // todo decode Chart
                return [
                    'actions' => $this->decodeActionPermissions($permissions),
                    'scopes'  => $this->decodeScopePermissions($permissions, $renderingId),
                ];
            },
            self::TTL
        );
    }

    private function decodeActionPermissions(array $rawPermissions): array
    {
        $actions = [];
        foreach ($rawPermissions['data']['collections'] as $collectionName => $permission) {
            foreach ($permission['collection'] as $actionName => $userIds) {
                $shortName = Str::before($actionName, 'Enabled');
                $userIds = $userIds instanceof Boolean ? [$this->caller->getId()] : $userIds;
                $actions["$shortName:$collectionName"] = $userIds;
            }

            foreach ($permission['actions'] as $actionName => $actionPermissions) {
                $userIds = $actionPermissions['triggerEnabled'];
                $userIds = $userIds instanceof Boolean ? [$this->caller->getId()] : $userIds;

                $actions["custom:$actionName:$collectionName"] = $userIds;
            }
        }

        return $actions;
    }

    private function decodeScopePermissions(array $rawPermissions, int $renderingId): array
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

        return $scopes;
    }
}
