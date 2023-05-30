<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Binary;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\CollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Schema as SchemaUtils;
use Illuminate\Support\Collection as IlluminateCollection;
use Illuminate\Support\Str;

class BinaryCollection extends CollectionDecorator
{
    protected array $binaryFields = [];

    private array $useHexConversion = [];

    public function getFields(): IlluminateCollection
    {
        $fields = collect();

        /**
         * @var string $fieldName
         * @var ColumnSchema|RelationSchema $schema
         */
        foreach ($this->childCollection->getFields() as $fieldName => $schema) {
            if ($schema instanceof ColumnSchema && $schema->getColumnType() === PrimitiveType::BINARY) {
                $this->binaryFields[] = $fieldName;
                $schema->setColumnType(PrimitiveType::STRING);
                $schema->setValidation($this->replaceValidation($fieldName, $schema));
            }

            $fields->put($fieldName, $schema);
        }

        return $fields;
    }

    public function setBinaryMode(string $name, string $type): void
    {
        $field = $this->childCollection->getFields()[$name];

        if ($type !== 'datauri' && $type !== 'hex') {
            throw new \Exception('Invalid binary mode');
        }

        if ($field->getType() === 'Column' && $field->getColumnType() === PrimitiveType::BINARY) {
            $this->useHexConversion[$name] = $type === 'hex';
            $this->markSchemaAsDirty();
        } else {
            throw new \Exception('Expected a binary field');
        }
    }

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

    private function shouldUseHex(string $name): bool
    {
        if (in_array($name, $this->useHexConversion, true)) {
            return $this->useHexConversion[$name];
        }

        return (
            SchemaUtils::isPrimaryKey($this->childCollection, $name) ||
            SchemaUtils::isForeignKey($this->childCollection, $name)
        );
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

        // todo add function shouldUseHex()
        $useHex = false;

        return $this->convertValueHelper($toBackend, $path, $useHex, $value);
    }

    private function convertValueHelper(bool $toBackend, string $path, bool $useHex, $value)
    {
        if ($value) {
            if (in_array($path, $this->binaryFields, true)) {
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
                $value = $useHex ? hex2bin($value) : base64_decode(Str::after($value, 'base64,'));
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

        $mime = mime_content_type($value) ?? 'application/octet-stream';
        $data = base64_encode(stream_get_contents($value));

        return "data:$mime;base64,$data";
    }

    private function replaceValidation(string $name, ColumnSchema $schema): array
    {
        $validation = [];

        if ($this->shouldUseHex($name)) {
            $minlength = collect($schema->getValidation())->first(fn ($rule) => $rule['operator'] === Operators::LONGER_THAN)['value'] ?? null;
            $maxlength = collect($schema->getValidation())->first(fn ($rule) => $rule['operator'] === Operators::SHORTER_THAN)['value'] ?? null;
            $validation[] = ['operator' => 'Match', 'value' => '/^[0-9a-f]+$/'];
            if ($minlength) {
                $validation[] = ['operator' => Operators::LONGER_THAN, 'value' => $minlength * 2 + 1];
            }
            if ($maxlength) {
                $validation[] = ['operator' => Operators::SHORTER_THAN, 'value' => $maxlength * 2 - 1];
            }
        } else {
            $validation[] = ['operator' => 'Match', 'value' => '/^data:.*;base64,.*/'];
        }

        if (collect($schema->getValidation())->first(fn ($rule) => $rule['operator'] === Operators::PRESENT)) {
            $validation[] = ['operator' => Operators::PRESENT];
        }

        return $validation;
    }
}
