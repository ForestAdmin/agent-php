<?php

namespace ForestAdmin\AgentPHP\Agent\Facades;

use ForestAdmin\AgentPHP\Agent\Services\ForestSchemaInstrospection;
use JsonPath\JsonObject;

/**
 * Class ForestSchema
 *
 * @method static JsonObject getSchema()
 * @method static array getClass(string $collection)
 * @method static array getFields(string $collection)
 * @method static array getSmartFields(string $collection)
 * @method static array getSmartActions(string $collection)
 * @method static array getSmartRelationships(string $collection)
 * @method static null|string getTypeByField(string $collection, string $field)
 * @method static array getRelatedData(string $collection)
 *
 * @see ForestSchemaInstrospection
 */
class ForestSchema extends Facade
{
    public static function getFacadeObject()
    {
        return new ForestSchemaInstrospection();
    }
}
