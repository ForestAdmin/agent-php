<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations;

class PolymorphicManyToOneSchema extends RelationSchema
{
    protected string $type;

    public function __construct(
        protected string $foreignKeyTypeField,
        protected string $foreignKey,
        protected array $foreignKeyTargets,
        protected string $foreignCollections,
    ) {
        $this->type = 'PolymorphicManyToOne';
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getForeignKeyTypeField(): string
    {
        return $this->foreignKeyTypeField;
    }

    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }

    public function getForeignKeyTargets(): array
    {
        return $this->foreignKeyTargets;
    }

    public function getForeignCollections(): string
    {
        return $this->foreignCollections;
    }
}
