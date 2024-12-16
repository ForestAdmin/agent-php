<?php

namespace ForestAdmin\AgentPHP\DatasourceDoctrine;

use Doctrine\ORM\EntityManagerInterface;
use ForestAdmin\AgentPHP\BaseDatasource\BaseDatasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

use function ForestAdmin\config;

use Illuminate\Support\Str;
use Symfony\Bridge\Doctrine\ManagerRegistry;

/**
 * @codeCoverageIgnore
 */
class DoctrineDatasource extends BaseDatasource
{
    private ManagerRegistry $doctrine;

    private EntityManagerInterface $entityManager;

    /**
     * @throws \ReflectionException
     */
    public function __construct(
        $manager,
        array $databaseConfig,
        ?string $liveQueryConnections = null
    ) {
        // replace var %kernel.project_dir% by real path for Sqlite DB
        if (isset($databaseConfig['url'])
            && Str::contains($databaseConfig['url'], '%kernel.project_dir%')
            && config('projectDir') !== null
        ) {
            $databaseConfig['url'] = Str::replace('%kernel.project_dir%', config('projectDir'), $databaseConfig['url']);
        }

        parent::__construct($databaseConfig);

        if ($manager instanceof ManagerRegistry) {
            $this->doctrine = $manager;
            $this->entityManager = $this->doctrine->getManager();
            if (is_string($liveQueryConnections)) {
                $this->liveQueryConnections = [$liveQueryConnections => $this->doctrine->getConnection('default')];
            } else {
                $this->liveQueryConnections = [];
            }
        } else {
            // old version before introduction liveQueryConnections that use Doctrine
            $this->entityManager = $manager;
        }

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

    public function getLiveQueryConnections(): ?array
    {
        return $this->liveQueryConnections;
    }

    public function executeNativeQuery(string $connectionName, string $query, array $bind = []): array
    {
        if (! isset($this->liveQueryConnections[$connectionName])) {
            throw new ForestException("Native query connection '{$connectionName}' is unknown.", 422);
        }

        //        $connection = \config('database.connections.' . $this->liveQueryConnections[$connectionName]);
        //        $orm = new Manager();
        //        $orm->addConnection($connection);
        //
        //        return $orm->getDatabaseManager()->select($query, $bind);
    }

    public function setManagerRegistry(ManagerRegistry $managerRegistry): void
    {
        $this->managerRegistry = $managerRegistry;
    }
}
