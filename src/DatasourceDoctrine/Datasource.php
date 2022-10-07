<?php

namespace ForestAdmin\AgentPHP\DatasourceDoctrine;

use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource as ForestDatasource;
use Doctrine\ORM\EntityManagerInterface;

class Datasource extends ForestDatasource
{
    /**
     * @throws \ReflectionException
     */
    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
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
}
