<?php

namespace ForestAdmin\AgentPHP\Agent\Routes;

use ForestAdmin\AgentPHP\Agent\Services\ForestAdminHttpDriverServices;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;

abstract class AbstractCollectionRoute extends AbstractRoute
{
    public function __construct(
        protected ForestAdminHttpDriverServices $services,
        protected DatasourceContract $datasource,
        protected string $collectionName
    ) {
        parent::__construct($services);
    }

    protected function getCollection(): Collection
    {
        return $this->datasource->getCollection($this->collectionName);
    }
}
