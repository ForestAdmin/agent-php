<?php

use ForestAdmin\AgentPHP\Agent\Services\CacheServices;
use ForestAdmin\AgentPHP\Agent\Utils\Filesystem;

uses()
    ->beforeEach(
        function () {
            $filesystem = new Filesystem();
            $directory = sys_get_temp_dir() . '/forest-cache' ;
            $cache = new CacheServices($filesystem, $directory);
            $cache->flush();
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
