<?php

namespace ForestAdmin\AgentPHP\BaseDatasource;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use ForestAdmin\AgentPHP\BaseDatasource\Contracts\BaseDatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource as ForestDatasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use Illuminate\Database\Capsule\Manager;

class BaseDatasource extends ForestDatasource implements BaseDatasourceContract
{
    protected Manager $orm;

    protected Connection $doctrineConnection;

    public const DRIVERS = [
        'pgsql'        => 'pdo_pgsql',
        'postgresql'   => 'pdo_pgsql',
        'mariadb'      => 'pdo_mysql',
        'mysql'        => 'pdo_mysql',
        'sqlite'       => 'pdo_sqlite',
        'sqlsrv'       => 'pdo_sqlsrv',
    ];

    public function __construct(protected array $databaseConfig)
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
    }

    private function makeDoctrineConnection(array $databaseConfig): void
    {
        if (! isset($databaseConfig['driver']) && isset($databaseConfig['url'])) {
            $parsedUrl = parse_url($databaseConfig['url']);
            if ($parsedUrl === false) {
                throw new ForestException('Invalid database DSN URL provided.');
            }

            parse_str($parsedUrl['query'] ?? '', $queryParams);

            $databaseConfig = array_merge([
                'driver'   => $parsedUrl['scheme'] ?? null,
                'username' => $parsedUrl['user'] ?? null,
                'password' => $parsedUrl['pass'] ?? null,
                'host'     => $parsedUrl['host'] ?? null,
                'port'     => $parsedUrl['port'] ?? null,
                'database' => ltrim($parsedUrl['path'] ?? '', '/'),
            ], $queryParams, $databaseConfig);
        }

        if (! isset(self::DRIVERS[$databaseConfig['driver']])) {
            throw new ForestException("The given driver '{$databaseConfig['driver']}' is unknown, " .
                'only the following drivers are supported: ' . implode(', ', array_keys(self::DRIVERS)));
        }

        if ($databaseConfig['driver'] === 'sqlite') {
            $config = [
                'path' => $databaseConfig['database'],
            ];
        } else {
            $config = [
                'user'      => $databaseConfig['username'],
                'password'  => $databaseConfig['password'],
                'host'      => $databaseConfig['host'],
                'dbname'    => $databaseConfig['database'],
                'port'      => $databaseConfig['port'],
            ];
        }

        $config['driver'] = self::DRIVERS[$databaseConfig['driver']];
        $config['url'] = $databaseConfig['url'] ?? null;

        $this->doctrineConnection = DriverManager::getConnection($config);
    }

    /**
     * @codeCoverageIgnore
     */
    public function __serialize(): array
    {
        return [
            'collections'    => $this->collections,
            'charts'         => $this->charts,
            'databaseConfig' => $this->databaseConfig,
        ];
    }

    /**
     * @codeCoverageIgnore
     */
    public function __unserialize(array $data): void
    {
        $this->collections = $data['collections'];
        $this->charts = $data['charts'];
        $this->databaseConfig = $data['databaseConfig'];

        $this->makeOrm($this->databaseConfig);
        $this->makeDoctrineConnection($this->databaseConfig);
    }
}
