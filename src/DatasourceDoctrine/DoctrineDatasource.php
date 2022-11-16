<?php

namespace ForestAdmin\AgentPHP\DatasourceDoctrine;

use Doctrine\ORM\EntityManagerInterface;
use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource as ForestDatasource;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Support\Arr;

class DoctrineDatasource extends ForestDatasource
{
    protected Manager $orm;

    /**
     * @throws \ReflectionException
     */
    public function __construct(private EntityManagerInterface $entityManager, array $databaseConfig)
    {
        parent::__construct();
        $this->orm = new Manager();
        $this->orm->addConnection(
            [
                "driver"   => $databaseConfig['databaseDriver'],
                "host"     => $databaseConfig['databaseHost'],
                "port"     => $databaseConfig['databasePort'],
                "database" => $databaseConfig['databaseName'],
                "username" => $databaseConfig['databaseUsername'],
                "password" => $databaseConfig['databasePassword'],
            ]
        );
        $this->orm->bootEloquent();

        $this->generate();
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function generate(): void
    {
        $metas = $this->entityManager->getMetadataFactory()->getAllMetadata();
        foreach ($metas as $meta) {
            $this->addCollection(new Collection($this, $meta));
        }
    }

    /**
     * @return EntityManagerInterface
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    /**
     * @return Manager
     */
    public function getOrm(): Manager
    {
        return $this->orm;
    }
}
