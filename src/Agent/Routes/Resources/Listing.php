<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources;

use ForestAdmin\AgentPHP\Agent\Facades\JsonApi;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Utils\ContextFilterFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use Symfony\Component\HttpFoundation\JsonResponse;
use function ForestAdmin\cache;

class Listing extends CollectionRoute
{
    /*
     * import { Context } from 'koa';
        import Router from '@koa/router';

        import CollectionRoute from '../collection-route';
        import ContextFilterFactory from '../../utils/context-filter-factory';
        import QueryStringParser from '../../utils/query-string';

        export default class ListRoute extends CollectionRoute {
          setupRoutes(router: Router): void {
            router.get(`/${this.collection.name}`, this.handleList.bind(this));
          }

          public async handleList(context: Context) {
            await this.services.permissions.can(context, `browse:${this.collection.name}`);

            const scope = await this.services.permissions.getScope(this.collection, context);
            const paginatedFilter = ContextFilterFactory.buildPaginated(this.collection, context, scope);

            const records = await this.collection.list(
              QueryStringParser.parseCaller(context),
              paginatedFilter,
              QueryStringParser.parseProjectionWithPks(this.collection, context),
            );

            context.response.body = this.services.serializer.serializeWithSearchMetadata(
              this.collection,
              records,
              paginatedFilter.search,
            );
          }
        }

     */
    public function setupRoutes(): AbstractRoute
    {
        $this->addRoute(
            'list',
            'get',
            '/{collectionName}',
            fn ($args) => $this->handleRequest($args)
        );

        return $this;
    }

    /**
     * @throws \JsonException
     */
    public function handleRequest(array $args = [])
    {
        $datasource = cache('datasource');
        /** @var Collection $collection */
        $collection = $datasource->getCollection($args['collectionName']);
        $collection->hydrate($args);
        $request = Request::createFromGlobals();
        $scope = null;

        $paginatedFilter = ContextFilterFactory::buildPaginated($collection, $request, $scope);

        $records = $collection->list($paginatedFilter, new Projection());

        return new JsonResponse(JsonApi::render($records, $collection->getName()));
    }
}
