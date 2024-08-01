<?php

namespace ForestAdmin\AgentPHP\BaseDatasource;

use ForestAdmin\AgentPHP\BaseDatasource\Contracts\BaseDatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource as ForestDatasource;
use Illuminate\Database\Capsule\Manager;

class BaseDatasource extends ForestDatasource implements BaseDatasourceContract
{
    protected Manager $orm;

    public function __construct(array $databaseConfig)
    {
        parent::__construct();

        $this->makeOrm($databaseConfig);
    }

    /**
     * @return Manager
     */
    public function getOrm(): Manager
    {
        return $this->orm;
    }

    private function makeOrm(array $databaseConfig): void
    {
        $this->orm = new Manager();
        $this->orm->addConnection($databaseConfig);
        $this->orm->bootEloquent();
    }
}
