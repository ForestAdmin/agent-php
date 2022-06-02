<?php

namespace ForestAdmin\AgentPHP\Agent\Types;

class ForestServerField
{
    /**
     * @param PrimitiveTypes|PrimitiveTypes[] $type
     */
    public function __construct(
        protected string $field,
        protected array $type,
        protected ?string $defaultValue = null,
        protected ?array $enums,
        protected ?string $integration = null,
        protected bool $isFilterable = false,
        protected bool $isPrimaryKey = false,
        protected bool $isRequired = false,
        protected bool $isSortable = false,
        protected bool $isVirtual = false,
        protected ?string $reference = null,
        protected ?string $inverseOf = null,
        protected ?string $relationship
    ) {
    }
    /*
     * export type ForestServerField = Partial<{
  field: string;
  type: ForestServerColumnType;
  defaultValue: unknown;
  enums: null | string[];
  integration: null; // Always null on forest-express
  isFilterable: boolean;
  isPrimaryKey: boolean;
  isReadOnly: boolean;
  isRequired: boolean;
  isSortable: boolean;
  isVirtual: boolean; // Computed. Not sure what is done with that knowledge on the frontend.
  reference: null | string;
  inverseOf: null | string;
  relationship: 'BelongsTo' | 'BelongsToMany' | 'HasMany' | 'HasOne';
  validations: Array<{ message: null; type: ValidationType; value: unknown }>;
}>;
     */

}
