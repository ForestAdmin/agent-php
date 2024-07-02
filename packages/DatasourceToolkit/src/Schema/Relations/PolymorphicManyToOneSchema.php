<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations;

use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\RelationSchema;

class PolymorphicManyToOneSchema extends RelationSchema
{
    protected string $type;

    public function __construct(
        protected string $foreignKeyTypeField,
        protected string $foreignKey,
        protected array $foreignKeyTargets,
        protected array $foreignCollections,
    ) {
        parent::__construct('PolymorphicManyToOne');
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

    public function getForeignCollections(): array
    {
        return $this->foreignCollections;
    }

    /**
     * /!\ Method temporarily added to make the polymorphic relation work
     * @return array
     */
    public function getForeignCollectionNames(): array
    {
        return collect($this->foreignCollections)->map(fn ($collection) => str_replace('App\Models\\', '', $collection))->toArray();
    }
}
