<?php

namespace ForestAdmin\AgentPHP\DatasourceDoctrine;

use Doctrine\ORM\EntityManagerInterface;
use ForestAdmin\AgentPHP\BaseDatasource\BaseDatasource;
use Illuminate\Support\Str;
use function ForestAdmin\config;

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
        // replace var %kernel.project_dir% by real path for Sqlite DB
        if (isset($databaseConfig['url'])
            && Str::contains($databaseConfig['url'], '%kernel.project_dir%')
            && config('projectDir') !== null
        ) {
            $databaseConfig['url'] = Str::replace('%kernel.project_dir%', config('projectDir'), $databaseConfig['url']);
        }

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
