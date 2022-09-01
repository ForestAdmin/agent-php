<?php

namespace ForestAdmin\AgentPHP\Agent\Types;

// todo useful ?

class ForestServerField
{
    /**
     * @param PrimitiveTypes|PrimitiveTypes[] $type
     */
    public function __construct(
        protected string  $field,
        protected array   $type,
        protected bool    $isFilterable = false,
        protected bool    $isPrimaryKey = false,
        protected bool    $isRequired = false,
        protected bool    $isSortable = false,
        protected bool    $isVirtual = false,
        protected ?string $relationship = null,
        protected ?array  $enums = [],
        protected ?string $defaultValue = null,
        protected ?string $integration = null,
        protected ?string $reference = null,
        protected ?string $inverseOf = null,
    ) {
    }
}
