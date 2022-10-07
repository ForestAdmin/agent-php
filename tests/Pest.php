<?php

use ForestAdmin\AgentPHP\Agent\Services\CacheServices;
use ForestAdmin\AgentPHP\Agent\Utils\Filesystem;

const BEARER = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6MSwiZW1haWwiOiJqb2huLmRvZUBkb21haW4uY29tIiwiZmlyc3ROYW1lIjoiSm9obiIsImxhc3ROYW1lIjoiRG9lIiwidGVhbSI6IkRldmVsb3BlcnMiLCJyZW5kZXJpbmdJZCI6IjEwIiwidGFncyI6eyJzb21ldGhpbmciOiJ0YWdWYWx1ZSJ9LCJ0aW1lem9uZSI6IkV1cm9wZS9QYXJpcyIsInBlcm1pc3Npb25MZXZlbCI6ImFkbWluIn0.yCAGVg2Ef4a6uDbM6_VjlFobFwACJnyFtjkbo5lkEi4';
const SECRET = '34b6d9b573e160b957244c1082619bc5a9e36ee8abae5fe7d15991d08ac9f31d';

uses()
    ->beforeEach(
        function () {
            $filesystem = new Filesystem();
            $directory = sys_get_temp_dir() . '/forest-cache' ;
            $cache = new CacheServices($filesystem, $directory);
            $cache->flush();

            $_GET = [];
            $_POST = [];
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
