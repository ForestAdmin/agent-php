<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Computed;

use Closure;

class ComputedDefinition
{
    public function __construct(
        protected string $columnType,
        protected array $dependencies,
        protected Closure $values,
        protected bool $isBeforeRelation = false,
        protected ?string $defaultValue = null,
        protected array $enumValues = []
    ) {
    }

    public function getValues(...$args)
    {
        return call_user_func($this->values, $args);
    }

    /**
     * @return bool
     */
    public function isBeforeRelation(): bool
    {
        return $this->isBeforeRelation;
    }

    /**
     * @return string
     */
    public function getColumnType(): string
    {
        return $this->columnType;
    }

    /**
     * @return array
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * @return string|null
     */
    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }

    /**
     * @return array|null
     */
    public function getEnumValues(): ?array
    {
        return $this->enumValues;
    }
}
