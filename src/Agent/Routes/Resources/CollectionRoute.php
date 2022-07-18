<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources;

use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Services\ForestAdminHttpDriverServices;
use ForestAdmin\AgentPHP\Agent\Utils\ContextFilterFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use function ForestAdmin\cache;

abstract class CollectionRoute extends AbstractRoute
{
    protected Collection $collection;

    protected PaginatedFilter $paginatedFilter;

    public function __construct(protected ForestAdminHttpDriverServices $services)
    {
        parent::__construct($services);
    }

    abstract public function handleRequest(array $args = []): array;

    public function build(array $args = []): void
    {
        $datasource = cache('datasource');
        $this->collection = $datasource->getCollection($args['collectionName']);
        $this->collection->hydrate($args);
        $request = Request::createFromGlobals();
        $scope = null;

        $this->paginatedFilter = ContextFilterFactory::buildPaginated($this->collection, $request, $scope);
    }
}
