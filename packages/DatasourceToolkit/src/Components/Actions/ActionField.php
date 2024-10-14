<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions;

class ActionField
{
    public function __construct(
        protected string $type,
        protected string $label,
        protected string $id,
        protected bool $watchChanges = false,
        protected ?string $description = null,
        protected bool $isRequired = false,
        protected bool $isReadOnly = false,
        protected mixed $value = null,
        protected ?array $enumValues = null,
        protected ?string $collectionName = null,
    ) {
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getType(): string
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
     * @param mixed $value
     */
    public function setValue(mixed $value): void
    {
        $this->value = $value;
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
