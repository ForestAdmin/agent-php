<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Write\WriteReplace;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\CollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\RecordValidator;
use Illuminate\Support\Collection as IlluminateCollection;

class WriteReplaceCollection extends CollectionDecorator
{
    private array $handlers = [];

    private array $used = [];

    public function replaceFieldWriting(string $fieldName, \Closure $definition): void
    {
        if (! $this->getFields()->keys()->contains($fieldName)) {
            throw new ForestException('The given field "' . $fieldName . '" does not exist on the ' . $this->getName() . ' collection.');
        }

        $this->handlers[$fieldName] = $definition;
        $this->markSchemaAsDirty();
    }

    public function getFields(): IlluminateCollection
    {
        $fields = $this->childCollection->getFields();

        foreach ($this->handlers as $fieldName => $handler) {
            $fields[$fieldName]->setIsReadOnly($handler === null);
        }

        return $fields;
    }

    public function create(Caller $caller, array $data)
    {
        $newRecords = $this->rewritePatch($caller, 'create', $data);

        return parent::create($caller, $newRecords);
    }

    public function update(Caller $caller, Filter $filter, array $patch)
    {
        $newPatch = $this->rewritePatch($caller, 'update', $patch);
        dd($newPatch);

        parent::update($caller, $filter, $newPatch);
    }

    private function rewritePatch(Caller $caller, string $action, array $patch): array
    {
        if (! in_array($action, ['create', 'update'], true)) {
            throw new ForestException("The action $action is not allowed (only create or update)");
        }

        // We rewrite the patch by applying all handlers on each field.
        $context = new WriteCustomizationContext($this, $caller, $action, $patch);
        $patches = collect($patch)->map(fn ($item, $key) => $this->rewriteKey($context, $key));

        // We now have a list of patches (one per field) that we can merge.
        $newPatch = $this->deepMerge(...$patches); //->toArray()
        // Check that the customer handlers did not introduce invalid data.
        if (count($newPatch) > 0) {
            RecordValidator::validate($this, $newPatch);
        }

        return $newPatch;
    }

    private function rewriteKey(WriteCustomizationContext $context, string $key)//: array
    {
        if (! in_array($key, $this->used, true)) {
            $this->used[] = $key;
        } else {
            throw new ForestException("Conflict value on the field $key. It received several values.");
        }

        $field = $this->getFields()[$key];
        // Handle Column fields.
        if ($field?->getType() === 'Column') {
            // We either call the customer handler or a default one that does nothing.
            $handler = $this->handlers[$key] ?? static fn ($v) => [$key => $v];
            $fieldPatch = isset($context->getRecord()[$key]) && $handler($context->getRecord()[$key], $context) ? $handler($context->getRecord()[$key], $context) : [];

            if (! is_array($fieldPatch)) {
                throw new ForestException("The write handler of $key should return an function or nothing.");
            }

            // Isolate change to our own value (which should not recurse) and the rest which should
            // trigger the other handlers.
            $value = $fieldPatch[$key] ?? null;
            unset($fieldPatch[$key]);
            $newPatch = $this->rewritePatch($context->getCaller(), $context->getAction(), $fieldPatch);

            return $value !== null ? $this->deepMerge([$key => $value], $newPatch) : $newPatch;
        }

        // Handle relation fields.
        if ($field?->getType() === 'ManyToOne' || $field?->getType() === 'OneToOne') {
            // Delegate relations to the appropriate collection.
            $relation = $this->dataSource->getCollection($field->getForeignCollection());

            return [$key => $relation->rewritePatch($context->getCaller(), $context->getAction(), $context->getRecord()[$key])];
        }

        throw new ForestException("Unknown field : $key");
    }

    /**
     * Recursively merge patches into a single one ensuring that there is no conflict.
     */
    private function deepMerge(...$patches): array
    {
        $acc = [];

        foreach ($patches as $key => $patch) {
            $patch = ! is_array($patch) ? [$key => $patch] : $patch;

            foreach ($patch as $subKey => $subValue) {
                if (! array_key_exists($subKey, $acc)) {
                    $acc[$subKey] = $subValue;
                } elseif (is_array($subValue)) {
                    dd('relation');
                    $acc[$subKey] = $this->deepMerge([$acc[$subKey], $subValue]);
                } else {
                    throw new ForestException("Conflict value on the field $subKey. It received several values.");
                }
            }
        }

        return $acc;
    }
}
