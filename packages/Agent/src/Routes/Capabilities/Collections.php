<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Capabilities;

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractCollectionRoute;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use Illuminate\Support\Str;

class Collections extends AbstractCollectionRoute
{
    public function setupRoutes(): AbstractRoute
    {
        $this->addRoute(
            'forest.capabilities.collections',
            'post',
            '/_internal/capabilities',
            fn ($args) => $this->handleRequest($args)
        );

        return $this;
    }

    public function handleRequest(array $args = []): array
    {
        $datasource = AgentFactory::get('datasource');
        $collections = $this->request->get('collectionNames') ?? $datasource->getCollections()->keys()->toArray();

        $result = array_map(function ($collectionName) use ($datasource) {
            $collection = $datasource->getCollection($collectionName);
            $fields = $collection->getFields()->filter(function ($field) {
                return $field instanceof ColumnSchema;
            })->map(function ($field, $name) {
                return [
                    'name'      => $name,
                    'type'      => $field->getColumnType(),
                    'operators' => collect($field->getFilterOperators())->map(function ($operator) {
                        return Str::studly($operator);
                    })->toArray(),
                ];
            })->values()->toArray();

            return [
                'name'   => $collection->getName(),
                'fields' => $fields,
            ];
        }, $collections);

        return [
            'content' => [
                'collections' => $result,
            ],
            'status'  => 200,
        ];
    }
}
