<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Binary;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\CollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use Illuminate\Support\Str;

class BinaryCollection extends CollectionDecorator
{
    // issue with getFields()
//    public function getFields(): IlluminateCollection
//    {
//        $fields = collect();
//        echo 1;
//        /**
//         * @var string $fieldName
//         * @var ColumnSchema|RelationSchema $schema
//         */
//        foreach ($this->childCollection->getFields() as $fieldName => $schema) {
//            if ($schema instanceof ColumnSchema && $schema->getColumnType() === PrimitiveType::BINARY) {
//                $schema->setColumnType(PrimitiveType::STRING);
//                // todo update validations
//            }
//
//            $fields->put($fieldName, $schema);
//        }
//
//        return $fields;
//    }

    public function create(Caller $caller, array $data)
    {
        $dataWithBinary = $this->convertRecord(true, $data);
        $record = $this->childCollection->create($caller, $dataWithBinary);

        return $this->convertRecord(false, $record);
    }

    public function list(Caller $caller, PaginatedFilter $filter, Projection $projection): array
    {
        $records = $this->childCollection->list($caller, $filter, $projection);

        return collect($records)->map(fn ($record) => $this->convertRecord(false, $record))->toArray();
    }

    public function update(Caller $caller, Filter $filter, array $patch)
    {
        $patchWithBinary = $this->convertRecord(true, $patch);

        $this->childCollection->update($caller, $filter, $patchWithBinary);
    }

    public function aggregate(Caller $caller, Filter $filter, Aggregation $aggregation, ?int $limit = null, ?string $chartType = null)
    {
        return $this->childCollection->aggregate($caller, $filter, $aggregation, $limit);
    }

    private function convertRecord(bool $toBackend, array $record): array
    {
        return collect($record)->map(fn ($value, $path) => $this->convertValue($toBackend, $path, $value))->toArray();
    }

    private function convertValue(bool $toBackend, string $path, $value)
    {
        $field = Str::before($path, ':');
        $schema = $this->childCollection->getFields()->get($field);

        if (! $schema instanceof ColumnSchema) {
            /** @var self $foreignCollection */
            $foreignCollection = $this->dataSource->getCollection($schema->getForeignCollection());

            $field = Str::after($path, ':');

            return $field
                ? $foreignCollection->convertValue($toBackend, $field, $value)
                : $foreignCollection->convertRecord($toBackend, $value);
        }

        $useHex = false;

        return $this->convertValueHelper($toBackend, $schema->getColumnType(), $useHex, $value);
    }

    private function convertValueHelper(bool $toBackend, string $columnType, bool $useHex, $value)
    {
        if ($value) {
            if ($columnType === PrimitiveType::BINARY) {
                return $this->convertScalar($toBackend, $useHex, $value);
            }

            // Never in this case with PHP agent ???
//            if (Array.isArray(columnType)) {
//                const newValues = (value as unknown[]).map(v =>
//                    this.convertValueHelper(toBackend, columnType[0], useHex, v),
//                );
//
//            return Promise.all(newValues);
//            }
//
//            if (typeof columnType !== 'string') {
//                const entries = Object.entries(columnType).map(async ([key, type]) => [
//                    key,
//                    await this.convertValueHelper(toBackend, type, useHex, value[key]),
//                ]);
//
//                return Object.fromEntries(await Promise.all(entries));
//            }
        }

        return $value;
    }

    private function convertScalar(bool $toBackend, bool $useHex, $value)
    {
        if ($toBackend) {
            if (is_string($value)) {
                $value = base64_decode(Str::after($value, 'base64,'));
                $fp = fopen('php://temp', 'rb+');
                fwrite($fp, $value);
                fseek($fp, 0);
                $value = $fp;
            }

            return $value;
        }

        if ($useHex) {
            return bin2hex(stream_get_contents($value));
        }


        // todo: get mime type from resource, impossible ?
        $mime = 'application/octet-stream';
        $data = base64_encode(stream_get_contents($value));

        return "data:$mime;base64,$data";
    }
}
