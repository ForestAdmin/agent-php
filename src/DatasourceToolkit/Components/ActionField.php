<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Concerns\ActionFieldType;

class ActionField
{
    public function __construct(
        protected ActionFieldType $type,
        protected string $label,
        protected bool $watchChanges,
        protected ?string $description = null,
        protected bool $isRequired = false,
        protected bool $isReadOnly = false,
        protected mixed $value = null,
        protected ?array $enumValues = null,
        protected ?string $collectionName = null,
    ) {
    }

    public static function buildFromDynamicField(DynamicField $field)
    {
        return new static(
            type: $field->getType(),
            label: $field->getLabel(),
            description: $field->getDescription(),
            isRequired: $field->isRequired(),
            isReadOnly: $field->isReadOnly(),
            value: $field->getValue(),
            enumValues: $field->getEnumValues(),
            collectionName: $field->getCollectionName(),
        );
    }

    /**
     * @return ActionFieldType
     */
    public function getType(): ActionFieldType
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return bool
     */
    public function isWatchChanges(): bool
    {
        return $this->watchChanges;
    }

    /**
     * @param bool $watchChanges
     */
    public function setWatchChanges(bool $watchChanges): void
    {
        $this->watchChanges = $watchChanges;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    /**
     * @return bool
     */
    public function isReadOnly(): bool
    {
        return $this->isReadOnly;
    }

    /**
     * @return mixed|null
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * @return array|null
     */
    public function getEnumValues(): ?array
    {
        return $this->enumValues;
    }

    /**
     * @return string|null
     */
    public function getCollectionName(): ?string
    {
        return $this->collectionName;
    }
}
