<?php

namespace ForestAdmin\AgentPHP\DatasourceDoctrine;

use Doctrine\ORM\EntityManagerInterface;
use ForestAdmin\AgentPHP\BaseDatasource\BaseDatasource;

/**
 * @codeCoverageIgnore
 */
class DoctrineDatasource extends BaseDatasource
{
    /**
     * @throws \ReflectionException
     */
    public function __construct(private EntityManagerInterface $entityManager, array $databaseConfig)
    {
        parent::__construct($databaseConfig);

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
