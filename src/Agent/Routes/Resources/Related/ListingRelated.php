<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources\Related;

use ForestAdmin\AgentPHP\Agent\Routes\AbstractRelationRoute;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Utils\ContextFilterFactory;
use ForestAdmin\AgentPHP\Agent\Utils\Csv;
use ForestAdmin\AgentPHP\Agent\Utils\Id;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Collection as CollectionUtils;
use Illuminate\Support\Str;
use League\Csv\Writer;

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
        if (Str::endsWith($args['relationName'], '.csv')) {
            $args['relationName'] = Str::replaceLast('.csv', '', $args['relationName']);

            return $this->handleRequestCsv($args);
        }

        $this->build($args);
        $this->permissions->can('browse:' . $this->childCollection->getName());
        $scope = $this->permissions->getScope($this->childCollection);
        $this->filter = ContextFilterFactory::buildPaginated($this->childCollection, $this->request, $scope);

        $id = Id::unpackId($this->collection, $args['id']);

        $records = CollectionUtils::listRelation(
            $this->collection,
            $id,
            $args['relationName'],
            $this->caller,
            $this->filter,
            QueryStringParser::parseProjectionWithPks($this->childCollection, $this->request)
        );

        return [
            'renderTransformer' => true,
            'name'              => $this->childCollection->getName(),
            'content'           => $records,
        ];
    }

    public function handleRequestCsv(array $args = []): array
    {
        $this->build($args);
        $this->permissions->can('browse:' . $this->childCollection->getName());
        $this->permissions->can('export:' . $this->childCollection->getName());

        $scope = $this->permissions->getScope($this->childCollection);
        $this->filter = ContextFilterFactory::build($this->collection, $this->request, $scope);

        $id = Id::unpackId($this->collection, $args['id']);

        $rows = CollectionUtils::listRelation(
            $this->collection,
            $id,
            $args['relationName'],
            $this->caller,
            $this->filter,
            QueryStringParser::parseProjectionWithPks($this->childCollection, $this->request),
            false
        );

        $filename = $this->request->input('filename', $this->childCollection->getName()) . '.csv';

        $csv = Writer::createFromString();
        $csv->insertOne(explode(',', $this->request->get('header')));
        foreach ($rows as $row) {
            $csv->insertOne(Csv::formatField($row));
        }

        $csv->toString();


        return [
            'content' => [
                $csv->output($filename),
            ],
            'headers' => [
                'Content-type'        => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ],
        ];
    }
}
