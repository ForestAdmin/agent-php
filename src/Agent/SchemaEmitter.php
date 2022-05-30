<?php

namespace ForestAdmin\AgentPHP\Agent;

use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;

class SchemaEmitter
{
    public const LIANA_NAME = 'forest-php-agent';

    public const LIANA_VERSION = '1.0.0-beta.22';

    public function getSerializedSchema(array $options, Datasource $datasource)
    {
        //return self::me;
        $schema = $options['isProduction'] ? $this->loadFromDisk($options['schemaPath']) : $this->generate($options['prefix'], $datasource);
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

    private function generate()
    {

    }

}
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
