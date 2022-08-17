<?php

namespace ForestAdmin\AgentPHP\Agent\Utils;

use ForestAdmin\AgentPHP\Agent\Http\ForestApiRequester;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

class ForestHttpApi
{
    public static function hasSchema(array $httpOptions, string $hash): bool
    {
//        try {
//            const response = await superagent
//            .post(new URL('/forest/apimaps/hashcheck', options.forestServerUrl).toString())
//            .send({ schemaFileHash: hash })
//            .set('forest-secret-key', options.envSecret);
//
//          return !response?.body?.sendSchema;
//        } catch (e) {
//            this.handleResponseError(e);
//        }

        return true;
    }

    public static function uploadSchema(array $jsonApiDocument): void
    {
        try {
            $forestApi = new ForestApiRequester();
            $forestApi->post(
                '/forest/apimaps',
                [],
                $jsonApiDocument
            );
        } catch (\Exception $e) {
            dd($e);
        }
    }

    public static function getPermissions(int $renderingId)
    {
        try {
            $forestApi = new ForestApiRequester();
            $response = $forestApi->get(
                '/liana/v3/permissions',
                compact('renderingId')
            );
            $body = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            if (isset($body['meta']['rolesACLActivated']) && ! $body['meta']['rolesACLActivated']) {
                throw new ForestException('Roles V2 are unsupported');
            }

            return $body;

//            $chartPermission = self::decodeChartPermissions($body);
//            $actionPermission = self::decodeActionPermissions($body);
//
//            return [
//                'actions'       => $chartPermission,
//                'actionsByUser' => $actionPermission,
//                //'scopes' => ForestHttpApi.decodeScopePermissions(body?.data?.renderings?.[renderingId] ?? {}),
//            ];
        } catch (\Exception $e) {
            // todo this.handleResponseError(e);
        }
    }

    private static function decodeChartPermissions(array $permissions = []): array
    {
        // todo
        //
        //    const serverCharts = Object.values<any>(chartsByType).flat();
        //    const frontendCharts = serverCharts.map(chart => ({
        //      type: chart.type,
        //      filters: chart.filter,
        //      aggregate: chart.aggregator,
        //      aggregate_field: chart.aggregateFieldName,
        //      collection: chart.sourceCollectionId,
        //      time_range: chart.timeRange,
        //      group_by_date_field: (chart.type === 'Line' && chart.groupByFieldName) || null,
        //      group_by_field: (chart.type !== 'Line' && chart.groupByFieldName) || null,
        //      limit: chart.limit,
        //      label_field: chart.labelFieldName,
        //      relationship_field: chart.relationshipFieldName,
        //    }));
        //
        //    const hashes = frontendCharts.map(chart =>
        //      hashObject(chart, {
        //        respectType: false,
        //        excludeKeys: key => chart[key] === null || chart[key] === undefined,
        //      }),
        //    );
        //
        //    hashes.forEach(hash => actions.add(`chart:${hash}`));
        return [];
    }

    private static function decodeActionPermissions(array $permissions = []): array
    {
        return [];
    }
//
//  /**
//   * Helper to format permissions into something easy to validate against
//   * Note that the format the server is sending varies depending on if we're using a remote or
//   * local environment.
//   */
//  private static decodeActionPermissions(
//    collections: any,
//    actions: Set<string>,
//    actionsByUser: RenderingPermissions['actionsByUser'],
//  ): void {
//    for (const [name, settings] of Object.entries<any>(collections)) {
//        for (const [actionName, userIds] of Object.entries<any>(settings.collection ?? {})) {
//            const shortName = actionName.substring(0, actionName.length - 'Enabled'.length);
//            if (typeof userIds === 'boolean') actions.add(`${shortName}:${name}`);
//        else actionsByUser[`${shortName}:${name}`] = new Set<number>(userIds);
//      }
//
//      for (const [actionName, actionPerms] of Object.entries<any>(settings.actions ?? {})) {
//            const userIds = actionPerms.triggerEnabled;
//            if (typeof userIds === 'boolean') actions.add(`custom:${actionName}:${name}`);
//        else actionsByUser[`custom:${actionName}:${name}`] = new Set<number>(userIds);
//      }
//    }
//  }
//
//  /** Helper to format permissions into something easy to validate against */
//  private static decodeScopePermissions(rendering: any): RenderingPermissions['scopes'] {
//    const scopes = {};
//
//    for (const [name, { scope }] of Object.entries<any>(rendering)) {
//    scopes[name] = scope && {
//        conditionTree: ConditionTreeFactory.fromPlainObject(scope.filter),
//        dynamicScopeValues: scope.dynamicScopesValues?.users ?? {},
//      };
//    }
//
//    return scopes;
//  }
//
//  private static handleResponseError(e: Error): void {
//    if (/certificate/i.test(e.message))
//      throw new Error(
//          'ForestAdmin server TLS certificate cannot be verified. ' +
//          'Please check that your system time is set properly.',
//      );
//
//    if ((e as ResponseError).response) {
//        const status = (e as ResponseError)?.response?.status;
//
//      // 0 == offline, 502 == bad gateway from proxy
//      if (status === 0 || status === 502)
//          throw new Error('Failed to reach ForestAdmin server. Are you online?');
//
//      if (status === 404)
//          throw new Error(
//              'ForestAdmin server failed to find the project related to the envSecret you configured.' +
//              ' Can you check that you copied it properly in the Forest initialization?',
//          );
//
//      if (status === 503)
//          throw new Error(
//              'Forest is in maintenance for a few minutes. We are upgrading your experience in ' +
//              'the forest. We just need a few more minutes to get it right.',
//          );
//
//      throw new Error(
//          'An unexpected error occured while contacting the ForestAdmin server. ' +
//          'Please contact support@forestadmin.com for further investigations.',
//      );
//    }
//
//    throw e;
//  }
}
