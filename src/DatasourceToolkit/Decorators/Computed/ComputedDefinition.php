<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Computed;

class ComputedDefinition
{
    public function __construct(
        protected string $columnType,
        protected array $dependencies,
        protected ?string $defaultValue = null,
        protected ?array $enumValues = null
    ) {
    }

    /* todo
     getValues(
    records: TRow<S, N>[],
    context: CollectionCustomizationContext<S, N>,
  ): Promise<unknown[]> | unknown[];
     */

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
