<?php

namespace ForestAdmin\AgentPHP\Agent\Services;

use function ForestAdmin\config;

use JsonPath\JsonObject;

class ForestSchemaInstrospection
{
    private JsonObject $schema;

    /**
     * @throws \JsonPath\InvalidJsonException
     */
    public function __construct()
    {
        $file = file_get_contents(config('schemaPath'));
        $this->schema = new JsonObject($file);
    }

    /**
     * @return JsonObject
     */
    public function getSchema(): JsonObject
    {
        return $this->schema;
    }

    /**
     * @param string $collection
     * @return string
     */
    public function getClass(string $collection): string
    {
        $data = $this->getSchema()->get("$..collections[?(@.name == '$collection')].class");

        return $data ? $data[0] : '';
    }

    /**
     * @param string $collection
     * @return array
     */
    public function getFields(string $collection): array
    {
        $data = $this->getSchema()->get("$..collections[?(@.name == '$collection')].class");

        return $data ? $data[0] : [];
    }

    /**
     * @param string $collection
     * @return array
     */
    public function getSmartFields(string $collection): array
    {
        $data = $this->getSchema()->get("$..collections[?(@.name == '$collection')].fields[?(@.is_virtual == true and @.reference == null)]");

        return $data ? collect($data)->mapWithKeys(fn ($item) => [$item['field'] => $item])->all() : [];
    }

    /**
     * @param string $collection
     * @return array
     */
    public function getSmartActions(string $collection): array
    {
        $data = $this->getSchema()->get("$..collections[?(@.name == '$collection')].actions[*]");

        return $data ?: [];
    }

    /**
     * @param string $collection
     * @return array
     */
    public function getSmartSegments(string $collection): array
    {
        $data = $this->getSchema()->get("$..collections[?(@.name == '$collection')].segments[*]");

        return $data ?: [];
    }

    /**
     * @param string $collection
     * @return array
     */
    public function getRelationships(string $collection): array
    {
        $data = $this->getSchema()->get("$..collections[?(@.name == '$collection')].fields[?(@.is_virtual == false and @.reference != null)]");

        return $data ? collect($data)->mapWithKeys(fn ($item) => [$item['field'] => $item])->all() : [];
    }

    /**
     * @param string $collection
     * @return array
     */
    public function getSmartRelationships(string $collection): array
    {
        $data = $this->getSchema()->get("$..collections[?(@.name == '$collection')].fields[?(@.is_virtual == true and @.reference != null)]");

        return $data ? collect($data)->mapWithKeys(fn ($item) => [$item['field'] => $item])->all() : [];
    }

    /**
     * @param string $collection
     * @param string $field
     * @return string|null
     */
    public function getTypeByField(string $collection, string $field): ?string
    {
        $data = $this->getSchema()->get("$..collections[?(@.name == '$collection')].fields[?(@.field == '$field')].type");

        return $data ? $data[0] : null;
    }

    /**
     * @param string $collection
     * @return array
     */
    public function getRelatedData(string $collection): array
    {
        //$data = $this->getSchema()->get("$[?(@.name == '$collection')].fields[?(@.relationship == 'HasMany' or @.relationship == 'BelongsToMany')].field");
        $data = $this->getSchema()->get("$..collections[?(@.name == '$collection')].fields[?(@.relationship == 'HasMany' or @.relationship == 'BelongsToMany')].field");
        $smartRelationships = $this->getSmartRelationships($collection);
        foreach ($smartRelationships as $relationship) {
            if (is_array($relationship['type'])) {
                $data[] = $relationship['field'];
            }
        }

        return $data ?: [];
    }
}

