<?php

namespace ForestAdmin\AgentPHP\BaseDatasource;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use ForestAdmin\AgentPHP\BaseDatasource\Contracts\BaseDatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource as ForestDatasource;
use Illuminate\Database\Capsule\Manager;

class BaseDatasource extends ForestDatasource implements BaseDatasourceContract
{
    protected Manager $orm;

    protected Connection $doctrineConnection;

    public function __construct(array $databaseConfig)
    {
        parent::__construct();

        $this->makeOrm($databaseConfig);
        $this->makeDoctrineConnection($databaseConfig);
    }

    /**
     * @return Manager
     */
    public function getOrm(): Manager
    {
        return $this->orm;
    }

    /**
     * @return Connection
     */
    public function getDoctrineConnection(): Connection
    {
        return $this->doctrineConnection;
    }

    private function makeOrm(array $databaseConfig): void
    {
        $this->orm = new Manager();
        $this->orm->addConnection($databaseConfig);
        $this->orm->bootEloquent();
    }

    private function makeDoctrineConnection(array $databaseConfig): void
    {
        $config = [
            'url'       => $databaseConfig['url'] ?? null,
            'driver'    => $databaseConfig['driver'],
            'user'      => $databaseConfig['username'],
            'password'  => $databaseConfig['password'],
            'host'      => $databaseConfig['host'],
            'dbname'    => $databaseConfig['database'],
        ];

        $this->doctrineConnection = DriverManager::getConnection($config);
    }
}
