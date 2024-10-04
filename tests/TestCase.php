<?php

namespace ForestAdmin\AgentPHP\Tests;

use Doctrine\DBAL\DriverManager;
use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Services\FileCacheServices;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Laravel\SerializableClosure\SerializableClosure;
use PHPUnit\Framework\TestCase as BaseTestCase;
use SQLite3;

class TestCase extends BaseTestCase
{
    public const DB_CONFIG = [
        'driver'   => 'sqlite',
        'database' => __DIR__ . '/Datasets/database.sqlite',
    ];

    public ?AgentFactory $agent = null;
    private Connection $connection;

    public array $bucket = [];

    public function __construct(string $name)
    {
        @class_alias(CacheMocked::class, FileCacheServices::class);

        parent::__construct($name);
    }

    public function buildAgent(Datasource $datasource, array $options = [])
    {
        $_SERVER['HTTP_AUTHORIZATION'] = BEARER;
        $_GET['timezone'] = 'Europe/Paris';

        $options = array_merge(
            AGENT_OPTIONS,
            $options
        );

        $this->agent = new AgentFactory($options);
        $datasource = clone $datasource;
        $this->invokeProperty($this->agent, 'datasource', $datasource);

        Cache::put('forestAgent', new SerializableClosure(fn () => $this->agent));
        Cache::put('forest.has_permission', true, 10);
    }

    /**
     * Call protected/private property of a class.
     * @param object $object
     * @param string $propertyName
     * @param null   $setData
     * @return mixed
     * @throws \ReflectionException
     */
    public function invokeProperty(object &$object, string $propertyName, $setData = null)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        if (! is_null($setData)) {
            $setData = $setData === 'null' ? null : $setData;
            $property->setValue($object, $setData);
        }

        return $property->getValue($object);
    }

    /**
     * Call protected/private method of a class.
     * @param object $object
     * @param string $methodName
     * @param array  $parameters
     * @return mixed
     * @throws \ReflectionException
     */
    public function invokeMethod(object &$object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    public function initDatabase(): void
    {
        if (file_exists(self::DB_CONFIG['database'])) {
            unlink(self::DB_CONFIG['database']);
        }

        new SQLite3(self::DB_CONFIG['database']);

        $manager = new Manager();
        $manager->addConnection(self::DB_CONFIG);
        $manager->bootEloquent();
        $this->connection = $manager->getConnection();

        $this->migrates();
        $this->seeds();
    }

    private function migrates(): void
    {
        $schema = $this->connection->getSchemaBuilder();

        $schema->create(
            'users',
            function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamps();
            }
        );

        $schema->create(
            'books',
            function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->integer('price');
                $table->foreignId('author_id')->nullable()->constrained('users')->onDelete('cascade');
                $table->dateTime('published_at')->nullable();
                $table->timestamps();
            }
        );

        $schema->create(
            'reviews',
            function (Blueprint $table) {
                $table->id();
                $table->string('author');
                $table->integer('rating');
                $table->timestamps();
            }
        );

        $schema->create(
            'book_review',
            function (Blueprint $table) {
                $table->id();
                $table->integer('book_id');
                $table->integer('review_id');
                $table->timestamps();
            }
        );

        $schema->create(
            'cars',
            function (Blueprint $table) {
                $table->id();
                $table->string('brand');
                $table->string('model');
                $table->timestamps();
            }
        );

        $schema->create(
            'owners',
            function (Blueprint $table) {
                $table->id();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('driver_licence_number')->nullable();
                $table->timestamps();
            }
        );

        $schema->create(
            'authors',
            function (Blueprint $table) {
                $table->id();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('email')->nullable();
                $table->timestamps();
            }
        );

        $schema->create(
            'car_owner',
            function (Blueprint $table) {
                $table->id();
                $table->integer('car_id');
                $table->integer('owner_id');
                $table->timestamps();
            }
        );

        $schema->create(
            'comments',
            function (Blueprint $table) {
                $table->id();
                $table->nullableMorphs('commentable');
                $table->text('body');
                $table->timestamps();
            }
        );
    }

    private function seeds(): void
    {
        $this->connection->table('users')->insert(
            [
                ['name' => 'user1', 'email' => 'user1@gmail.com'],
                ['name' => 'user2', 'email' => 'user2@gmail.com'],
                ['name' => 'user3', 'email' => 'user3@gmail.com'],
            ]
        );

        $this->connection->table('books')->insert(
            [
                ['title' => 'book1', 'price' => '10', 'published_at' => date('c'), 'author_id' => 1],
                ['title' => 'book2', 'price' => '10', 'published_at' => date('c'), 'author_id' => 2],
                ['title' => 'book3', 'price' => '10', 'published_at' => date('c'), 'author_id' => 3],
                ['title' => 'book4', 'price' => '10', 'published_at' => date('c'), 'author_id' => 1],
            ]
        );
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    public function getDoctrineConnection()
    {
        $config['driver'] = 'pdo_sqlite';
        $config['path'] = self::DB_CONFIG['database'];

        return DriverManager::getConnection($config);
    }
}
