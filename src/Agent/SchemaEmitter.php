<?php

namespace ForestAdmin\AgentPHP\Agent;

use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\GeneratorCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;

class SchemaEmitter
{
    public const LIANA_NAME = 'forest-php-agent';

    public const LIANA_VERSION = '1.0.0-beta.22';

    /**
     * @throws \JsonException
     */
    public function getSerializedSchema(array $options, Datasource $datasource)
    {
        $schema = $options['isProduction'] ? $this->loadFromDisk($options['schemaPath']) : $this->generate($options['prefix'], $datasource);

        if (! $options['isProduction']) {
            // todo create json file
            $pretty = json_encode($schema, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
            file_put_contents($options['schemaPath'], $pretty);
            //  writeFile(options.schemaPath, pretty, { encoding: 'utf-8' });
        }
        $hash = sha1(json_encode($schema, JSON_THROW_ON_ERROR));

        return self::serialize($schema, $hash);
    }

    /**
     * @return array
     */
    private function meta(): array
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

    private function loadFromDisk()
    {
    }

    private function generate(string $prefix, Datasource $datasource)
    {
        return $datasource
            ->getCollections()
            ->map(
                fn ($collection) => GeneratorCollection::buildSchema($prefix, $collection)
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
    private function serialize(array $schema, string $hash): array
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
            'meta'     => $meta,
        ];
    }
}

//private static async generate(prefix: string, dataSource: DataSource): Promise<RawSchema> {
//    const allCollectionSchemas = [];
//
//    const dataSourceCollectionSchemas = dataSource.collections.map(collection =>
//      SchemaGeneratorCollection.buildSchema(prefix, collection),
//    );
//    allCollectionSchemas.push(...dataSourceCollectionSchemas);
//
//    return Promise.all(allCollectionSchemas);
//  }





//
//static async getSerializedSchema(
//    options: Options,
//    dataSource: DataSource,
//): Promise<SerializedSchema> {
//    const schema: RawSchema = options.isProduction
//        ? await SchemaEmitter.loadFromDisk(options.schemaPath)
//      : await SchemaEmitter.generate(options.prefix, dataSource);
//
//    if (!options.isProduction) {
//        const pretty = stringify(schema, { maxLength: 80 });
//      await writeFile(options.schemaPath, pretty, { encoding: 'utf-8' });
//    }
//
//    const hash = crypto.createHash('sha1').update(JSON.stringify(schema)).digest('hex');
//
//    return SchemaEmitter.serialize(schema, hash);
//  }
