<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources;

use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Services\ForestAdminHttpDriverServices;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use function ForestAdmin\cache;

abstract class CollectionRoute extends AbstractRoute
{
    protected Datasource $datasource;

    public function __construct(protected ForestAdminHttpDriverServices $services)
    {
        parent::__construct($services);
        $this->datasource = cache('datasource');
    }

    /*protected get collection(): Collection {
    return this.dataSource.getCollection(this.collectionName);
  }

    constructor(
        services: ForestAdminHttpDriverServices,
        options: AgentOptionsWithDefaults,
        dataSource: DataSource,
        collectionName: string,
    ) {
    super(services, options);
    this.collectionName = collectionName;
    this.dataSource = dataSource;
    }*/

    abstract public function handleRequest(array $args = []);
}
