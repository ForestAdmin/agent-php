<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions;

class DynamicField
{
    public function __construct(
        protected string $type,
        protected string $label,
        protected ?string $description = null,
        protected bool $isRequired = false,
        protected bool $isReadOnly = false,
        protected ?\Closure $if = null,
        protected \Closure|string|null $value = null,
        protected \Closure|string|null $defaultValue = null,
        protected \Closure|string|null $collectionName = null,
        protected \Closure|array|null $enumValues = null,
    ) {
    }

    /**
     * @return \Closure|string|null
     */
    public function getCollectionName(): \Closure|string|null
    {
        return $this->collectionName;
    }

    /**
     * @return array|\Closure|null
     */
    public function getEnumValues(): array|\Closure|null
    {
        return $this->enumValues;
    }

    /**
     * @return \Closure|null
     */
    public function getIf(): ?\Closure
    {
        return $this->if;
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
     * @return \Closure|null
     */
    public function getValue(): \Closure|string|null
    {
        return $this->value;
    }

    /**
     * @return \Closure|null
     */
    public function getDefaultValue(): \Closure|string|null
    {
        return $this->defaultValue;
    }

    public function isStatic(): bool
    {
        foreach ($this as $field => $value) {
            if (is_callable($value)) {
                return false;
            }
        }

        return true;
    }
}
