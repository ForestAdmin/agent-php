<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Binary;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\CollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\PolymorphicManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Schema as SchemaUtils;
use Illuminate\Support\Collection as IlluminateCollection;
use Illuminate\Support\Str;

class BinaryCollection extends CollectionDecorator
{
    public const OPERATORS_WITH_VALUE_REPLACEMENT = [
        Operators::AFTER,
        Operators::BEFORE,
        Operators::CONTAINS,
        Operators::ENDS_WITH,
        Operators::EQUAL,
        Operators::GREATER_THAN,
        Operators::ICONTAINS,
        Operators::NOT_IN,
        Operators::IENDS_WITH,
        Operators::ISTARTS_WITH,
        Operators::LESS_THAN,
        Operators::NOT_CONTAINS,
        Operators::NOT_EQUAL,
        Operators::STARTS_WITH,
        Operators::IN,
    ];

    protected array $binaryFields = [];

    private array $useHexConversion = [];

    public function refineSchema(IlluminateCollection $childSchema): IlluminateCollection
    {
        /**
         * @var string $fieldName
         * @var ColumnSchema|RelationSchema $schema
         */
        foreach ($childSchema as $fieldName => $schema) {
            if ($schema instanceof ColumnSchema && $schema->getColumnType() === PrimitiveType::BINARY) {
                $this->binaryFields[] = $fieldName;
                $schema->setColumnType(PrimitiveType::STRING);
                $schema->setValidation($this->replaceValidation($fieldName, $schema));
            }

            $childSchema->put($fieldName, $schema);
        }

        return $childSchema;
    }

    public function setBinaryMode(string $name, string $type): void
    {
        $field = $this->childCollection->getFields()[$name];

        if ($field === null) {
            throw new \Exception('Field not found');
        }

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
        $filter = $this->refineFilter($caller, $filter);
        $records = $this->childCollection->list($caller, $filter, $projection);

        return collect($records)->map(fn ($record) => $this->convertRecord(false, $record))->toArray();
    }

    public function update(Caller $caller, Filter $filter, array $patch)
    {
        $filter = $this->refineFilter($caller, $filter);
        $patchWithBinary = $this->convertRecord(true, $patch);

        $this->childCollection->update($caller, $filter, $patchWithBinary);
    }

    public function aggregate(Caller $caller, Filter $filter, Aggregation $aggregation, ?int $limit = null, ?string $chartType = null)
    {
        $filter = $this->refineFilter($caller, $filter);
        $rows = $this->childCollection->aggregate($caller, $filter, $aggregation, $limit);

        return collect($rows)->map(function ($row) {
            return [
                'value' => $row['value'],
                'group' => collect($row['group'])->map(fn ($value, $path) => $this->convertValue(false, $path, $value))->toArray(),
            ];
        })->toArray();
    }

    public function refineFilter(Caller $caller, Filter|PaginatedFilter|null $filter): Filter|PaginatedFilter|null
    {
        return $filter?->override(conditionTree: $filter?->getConditionTree()?->replaceLeafs(fn ($leaf) => $this->convertConditionTreeLeaf($leaf)));
    }

    private function convertConditionTreeLeaf(ConditionTreeLeaf $leaf)
    {
        $prefix = Str::before($leaf->getField(), ':');
        $suffix = Str::contains($leaf->getField(), ':') ? Str::after($leaf->getField(), ':') : null;
        $schema = $this->childCollection->getFields()[$prefix];

        if ($schema->getType() !== 'Column') {
            $conditionTree = $this->dataSource->getCollection($schema->getForeignCollection())
                ->convertConditionTreeLeaf($leaf->override(field: $suffix));

            return $conditionTree->nest($prefix);
        }

        if (in_array($leaf->getOperator(), self::OPERATORS_WITH_VALUE_REPLACEMENT, true)) {
            $useHex = $this->shouldUseHex($prefix);

            return $leaf->override(value: $this->convertValueHelper(true, $prefix, $useHex, $leaf->getValue()));
        }

        return $leaf;
    }

    private function shouldUseHex(string $name): bool
    {
        if (in_array($name, array_keys($this->useHexConversion), true)) {
            return $this->useHexConversion[$name];
        }

        return (
            SchemaUtils::isPrimaryKey($this->childCollection, $name) ||
            SchemaUtils::isForeignKey($this->childCollection, $name)
        );
    }

    private function convertRecord(bool $toBackend, ?array $record): array
    {
        return collect($record)->map(fn ($value, $path) => $this->convertValue($toBackend, $path, $value))->toArray();
    }

    private function convertValue(bool $toBackend, string $fieldName, $value)
    {
        $prefix = Str::before($fieldName, ':');
        $suffix = Str::contains($fieldName, ':') ? Str::after($fieldName, ':') : null;
        $schema = $this->childCollection->getFields()->get($prefix);

        if ($schema instanceof PolymorphicManyToOneSchema || $schema === null) {
            return $value;
        }

        if (! $schema instanceof ColumnSchema) {
            $foreignCollection = $this->dataSource->getCollection($schema->getForeignCollection());

            return $suffix ? $foreignCollection->convertValue($toBackend, $suffix, $value) : $foreignCollection->convertRecord($toBackend, $value);
        }

        $binaryMode = $this->shouldUseHex($fieldName);

        return $this->convertValueHelper($toBackend, $fieldName, $binaryMode, $value);
    }

    private function convertValueHelper(bool $toBackend, string $path, bool $useHex, $value)
    {
        if ($value && in_array($path, $this->binaryFields, true)) {
            if (is_array($value)) {
                return collect($value)
                    ->map(fn ($v) => $this->convertValueHelper($toBackend, $path, $useHex, $v))
                    ->toArray();
            }


            return $this->convertScalar($toBackend, $useHex, $value);
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
            $minlength = collect($schema->getValidation())->firstWhere(fn ($rule) => $rule['operator'] === Operators::LONGER_THAN)['value'] ?? null;
            $maxlength = collect($schema->getValidation())->firstWhere(fn ($rule) => $rule['operator'] === Operators::SHORTER_THAN)['value'] ?? null;
            $validation[] = ['operator' => Operators::MATCH, 'value' => '/^[0-9a-f]+$/'];

            if ($minlength) {
                $validation[] = ['operator' => Operators::LONGER_THAN, 'value' => (int) $minlength * 2 + 1];
            }
            if ($maxlength) {
                $validation[] = ['operator' => Operators::SHORTER_THAN, 'value' => (int) $maxlength * 2 - 1];
            }
        } else {
            $validation[] = ['operator' => Operators::MATCH, 'value' => '/^data:.*;base64,.*/'];
        }

        if (collect($schema->getValidation())->first(fn ($rule) => $rule['operator'] === Operators::PRESENT)) {
            $validation[] = ['operator' => Operators::PRESENT];
        }

        return $validation;
    }
}
