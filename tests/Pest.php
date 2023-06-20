<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Services\CacheServices;
use ForestAdmin\AgentPHP\Agent\Utils\Filesystem;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;

const BEARER = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6MSwiZW1haWwiOiJqb2huLmRvZUBkb21haW4uY29tIiwiZmlyc3ROYW1lIjoiSm9obiIsImxhc3ROYW1lIjoiRG9lIiwidGVhbSI6IkRldmVsb3BlcnMiLCJyZW5kZXJpbmdJZCI6IjEwIiwidGFncyI6eyJzb21ldGhpbmciOiJ0YWdWYWx1ZSJ9LCJ0aW1lem9uZSI6IkV1cm9wZS9QYXJpcyIsInBlcm1pc3Npb25MZXZlbCI6ImFkbWluIn0.yCAGVg2Ef4a6uDbM6_VjlFobFwACJnyFtjkbo5lkEi4';

const AUTH_SECRET = '34b6d9b573e160b957244c1082619bc5a9e36ee8abae5fe7d15991d08ac9f31d';

const SECRET = '34b6d9b573e160b957244c1082619bc5a9e36ee8abae5fe7d15991d08ac9f31d';

const FOREST_PERMISSIONS_EXPIRATION_IN_SECONDS = 60;

define("AGENT_OPTIONS", [
    'projectDir'            => sys_get_temp_dir(),
    'cacheDir'              => sys_get_temp_dir() . '/forest-cache',
    'schemaPath'            => sys_get_temp_dir() . '/.forestadmin-schema.json',
    'authSecret'            => AUTH_SECRET,
    'envSecret'             => SECRET,
    'isProduction'          => false,
    'permissionExpiration'  => FOREST_PERMISSIONS_EXPIRATION_IN_SECONDS,
]);

uses()
    ->beforeEach(
        function () {
            $filesystem = new Filesystem();
            $directory = sys_get_temp_dir() . '/forest-cache' ;
            $cache = new CacheServices($filesystem, $directory);
            $cache->flush();

            $_GET = [];
            $_POST = [];

            Cache::put('forest.has_permission', true, 10);
        }
    )->in('Agent', 'DatasourceToolkit');

/**
 * Call protected/private property of a class.
 * @param object $object
 * @param string $propertyName
 * @param null   $setData
 * @return mixed
 * @throws \ReflectionException
 */
function invokeProperty(object &$object, string $propertyName, $setData = null)
{
    $reflection = new \ReflectionClass(get_class($object));
    $property = $reflection->getProperty($propertyName);
    $property->setAccessible(true);

    if (! is_null($setData)) {
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
function invokeMethod(object &$object, string $methodName, array $parameters = [])
{
    $reflection = new \ReflectionClass(get_class($object));
    $method = $reflection->getMethod($methodName);
    $method->setAccessible(true);

    return $method->invokeArgs($object, $parameters);
}


function buildAgent(Datasource $datasource, array $options = [])
{
    $_SERVER['HTTP_AUTHORIZATION'] = BEARER;
    $_GET['timezone'] = 'Europe/Paris';

    $options = array_merge(
        AGENT_OPTIONS,
        $options
    );

    $agent = new AgentFactory($options, []);
    $container = AgentFactory::getContainer();
    $container->set('datasource', $datasource);
    invokeProperty($agent, 'container', $container);

    return $agent;
}

function migrateAndSeed(Connection $connection): void
{
    $schema = $connection->getSchemaBuilder();
    $schema->dropAllTables();

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
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->dateTime('published_at')->nullable();
            $table->timestamps();
        }
    );

    $connection->table('users')->insert(
        [
            ['name' => 'user1', 'email' => 'user1@gmail.com'],
            ['name' => 'user2', 'email' => 'user2@gmail.com'],
            ['name' => 'user3', 'email' => 'user3@gmail.com'],
        ]
    );

    $connection->table('books')->insert(
        [
            ['title' => 'book1', 'price' => '10', 'published_at' => date('c'), 'author_id' => 1],
            ['title' => 'book2', 'price' => '10', 'published_at' => date('c'), 'author_id' => 2],
            ['title' => 'book3', 'price' => '10', 'published_at' => date('c'), 'author_id' => 3],
            ['title' => 'book4', 'price' => '10', 'published_at' => date('c'), 'author_id' => 1],
        ]
    );
}
