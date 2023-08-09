<?php

namespace ForestAdmin\AgentPHP\Agent\Utils\ForestSchema;

use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

use function ForestAdmin\config;

class SchemaEmitter
{
    public const LIANA_NAME = 'agent-php';

    public const LIANA_VERSION = '1.6.0';

    public static function getSerializedSchema(Datasource $datasource)
    {
        if (config('isProduction')) {
            if (config('schemaPath') && file_exists(config('schemaPath'))) {
                $schema = json_decode(file_get_contents(config('schemaPath')), true, 512, JSON_THROW_ON_ERROR);
            } else {
                throw new ForestException('The .forestadmin-schema.json file doesn\'t exist');
            }
        } else {
            $schema = self::generate($datasource);
            $hash = sha1(json_encode($schema, JSON_THROW_ON_ERROR));
            $schema = [
                'meta'        => self::meta($hash),
                'collections' => $schema,
            ];

            $pretty = json_encode($schema, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
            file_put_contents(config('schemaPath'), $pretty);
        }

        return self::serialize($schema);
    }

    private static function meta(string $hash): array
    {
        return [
            'liana'          => self::LIANA_NAME,
            'liana_version'  => self::LIANA_VERSION,
            'stack'          => [
                'engine'         => 'php',
                'engine_version' => phpversion(),
            ],
            'schemaFileHash' => $hash,
        ];
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

    private static function serialize(array $schema): array
    {
        $data = [];
        $included = [];
        foreach ($schema['collections'] as $collection) {
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
            'meta'     => $schema['meta'],
        ];
    }

    private static function getSmartFeaturesByCollection(string $type, array $data, bool $withAttributes = false)
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
