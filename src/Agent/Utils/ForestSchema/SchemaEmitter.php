<?php

namespace ForestAdmin\AgentPHP\Agent\Utils\ForestSchema;

use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;

use function ForestAdmin\config;

class SchemaEmitter
{
    public const LIANA_NAME = 'agent-php';

    public const LIANA_VERSION = '1.0.0-beta.4';

    /**
     * @throws \JsonException
     * @throws \ErrorException
     */
    public static function getSerializedSchema(Datasource $datasource)
    {
        $schema = config('isProduction') ? self::loadFromDisk() : self::generate($datasource);

        if (! config('isProduction')) {
            $pretty = json_encode(['meta' => self::meta(), 'collections' => $schema], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
            file_put_contents(config('schemaPath'), $pretty);
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
                'engine_version' => phpversion(),
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
        $included = [];
        $meta = self::meta();
        $meta['schemaFileHash'] = $hash;

        foreach ($schema as $collection) {
            $collectionActions = $collection['actions'];
            $collectionSegments = $collection['segments'];
            unset($collection['actions'], $collection['segments']);

            $included[] = self::getSmartFeaturesByCollection('actions', $collectionActions, true);
            $included[] = self::getSmartFeaturesByCollection('segments', $collectionSegments, true);

            $data[] = [
                'id'            => $collection['name'],
                'type'          => 'collections',
                'attributes'    => $collection,
                'relationships' => [
                    'actions'  => [
                        'data' => self::getSmartFeaturesByCollection('actions', $collectionActions),
                    ],
                    'segments' => [
                        'data' => self::getSmartFeaturesByCollection('segments', $collectionSegments),
                    ],
                ],
            ];
        }

        return [
            'data'     => $data,
            'included' => array_merge(...$included),
            'meta'     => $meta,
        ];
    }

    /**
     * @param string $type
     * @param array  $data
     * @param bool   $withAttributes
     * @return array
     */
    private static function getSmartFeaturesByCollection(string $type, array $data, bool $withAttributes = false): array
    {
        $smartFeatures = [];

        foreach ($data as $value) {
            $smartFeature = [
                'id'   => $value['id'],
                'type' => $type,
            ];
            if ($withAttributes) {
                $smartFeature['attributes'] = $value;
            }
            $smartFeatures[] = $smartFeature;
        }

        return $smartFeatures;
    }
}
