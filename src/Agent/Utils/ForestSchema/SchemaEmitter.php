<?php

namespace ForestAdmin\AgentPHP\Agent\Utils\ForestSchema;

use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use function ForestAdmin\config;

class SchemaEmitter
{
    public const LIANA_NAME = 'laravel-forestadmin';
//    public const LIANA_NAME = 'forest-php-agent';

    public const LIANA_VERSION = '1.0.0-beta.22';

    /**
     * @throws \JsonException
     * @throws \ErrorException
     */
    public static function getSerializedSchema(Datasource $datasource)
    {
        $schema = config('isProduction') ? self::loadFromDisk() : self::generate($datasource);

        if (! config('isProduction')) {
            // todo create json file
            $pretty = json_encode($schema, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
            file_put_contents(config('schemaPath'), $pretty);
            //  writeFile(options.schemaPath, pretty, { encoding: 'utf-8' });
        }
        $hash = sha1(json_encode($schema, JSON_THROW_ON_ERROR));

        return self::serialize($schema, $hash);
    }

    /**
     * @return array
     */
    private static function meta(): array
    {
        return [
            'liana'         => self::LIANA_NAME,
            'liana_version' => self::LIANA_VERSION,
            'stack'         => [
                'engine'         => 'php',
                'engine_version' => '', /* TODO */
                'database_type'  => '',  /* TODO */
                'orm_version'    => '',  /* TODO */
            ],
        ];
    }

    private static function loadFromDisk()
    {
    }

    private static function generate(Datasource $datasource)
    {
        return $datasource
            ->getCollections()
            ->map(
                fn ($collection) => GeneratorCollection::buildSchema($collection)
            )
            ->sortBy('name')
            ->values()
            ->toArray();
    }

    /**
     * @param array  $schema
     * @param string $hash
     * @return array
     */
    private static function serialize(array $schema, string $hash): array
    {
        $data = [];
        $meta = self::meta();
        $meta['schemaFileHash'] = $hash;

        foreach ($schema as $collection) {
//            $collectionActions = $collection['actions'];
//            $collectionSegments = $collection['segments'];
            unset($collection['actions'], $collection['segments']);

            $data[] = [
                'id'            => $collection['name'],
                'type'          => 'collections',
                'attributes'    => $collection,
                'relationships' => [
                    'actions'  => [
                        'data' => [],
                    ],
                    'segments' => [
                        'data' => [],
                    ],
                ],
            ];
        }

        return [
            'data'     => $data,
            'included' => [], // todo
            'meta'     => $meta,
        ];
    }
}
