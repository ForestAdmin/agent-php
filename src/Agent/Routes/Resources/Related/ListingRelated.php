<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources\Related;

use ForestAdmin\AgentPHP\Agent\Routes\AbstractRelationRoute;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Utils\ContextFilterFactory;
use ForestAdmin\AgentPHP\Agent\Utils\Id;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Collection as CollectionUtils;

class ListingRelated extends AbstractRelationRoute
{
    public function setupRoutes(): AbstractRoute
    {
        $this->addRoute(
            'forest.related.list',
            'get',
            '/{collectionName}/{id}/relationships/{relationName}',
            fn ($args) => $this->handleRequest($args)
        );

        return $this;
    }

    public function handleRequest(array $args = []): array
    {
        $this->build($args);
        $scope = $this->permissions->getScope($this->childCollection);
        $this->filter = ContextFilterFactory::buildPaginated($this->childCollection, $this->request, $scope);

        $id = Id::unpackId($this->collection, $args['id']);

        $records = CollectionUtils::listRelation(
            $this->collection,
            $id,
            $args['relationName'],
            QueryStringParser::parseCaller($this->request),
            $this->filter,
            QueryStringParser::parseProjectionWithPks($this->childCollection, $this->request)
        );

        return [
            'renderTransformer' => true,
            'name'              => $this->childCollection->getName(),
            'content'           => $records,
        ];
    }

//    public async handleListRelated(context: Context): Promise<void> {
//        await this.services.permissions.can(context, `browse:${this.collection.name}`);
//
//    const parentId = IdUtils.unpackId(this.collection.schema, context.params.parentId);
//    const scope = await this.services.permissions.getScope(this.foreignCollection, context);
//        const paginatedFilter = ContextFilterFactory.buildPaginated(
//                this.foreignCollection,
//                context,
//                scope,
//            );
//
//        const records = await CollectionUtils.listRelation(
//        this.collection,
//        parentId,
//        this.relationName,
//        QueryStringParser.parseCaller(context),
//        paginatedFilter,
//        QueryStringParser.parseProjectionWithPks(this.foreignCollection, context),
//    );
//
//        context.response.body = this.services.serializer.serializeWithSearchMetadata(
//                this.foreignCollection,
//                records,
//                paginatedFilter.search,
//            );
//      }
}
