<?php

namespace ForestAdmin\AgentPHP\DatasourceDoctrine;

use Doctrine\ORM\EntityManagerInterface;
use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\BaseDatasource\BaseDatasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;

use function ForestAdmin\config;

use Illuminate\Support\Str;

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

    public function addCollection(CollectionContract $collection): void
    {
        if (! $this->collections->has($collection->getName())) {
            $this->collections->put($collection->getName(), $collection);
        } else {
            $existingCollection = $this->collections->get($collection->getName());
            AgentFactory::$logger?->info(
                'can not add the collection ' . $collection->getName() .
                ' (' . $collection->getClassName() . '), because another collection with the same name exists from: ' .
                $existingCollection->getClassName()
            );
        }
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
