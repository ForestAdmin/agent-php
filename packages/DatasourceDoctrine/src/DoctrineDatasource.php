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
        null|string|array $liveQueryConnections = null
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
                $this->liveQueryConnections = [$liveQueryConnections => $this->doctrine->getDefaultConnectionName()];
            } elseif (is_array($liveQueryConnections)) {
                $this->liveQueryConnections = $liveQueryConnections;
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

        return $this->orm->getDatabaseManager()->select($query, $bind);
    }

    /**
     * @codeCoverageIgnore
     */
    public function __serialize(): array
    {
        return array_merge(
            parent::__serialize(),
            [
                'liveQueryConnections' => $this->liveQueryConnections,
            ]
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);

        $this->liveQueryConnections = $data['liveQueryConnections'];
    }
}
