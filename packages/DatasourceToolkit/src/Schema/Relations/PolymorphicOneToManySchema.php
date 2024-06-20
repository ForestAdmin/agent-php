<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations;

use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\RelationSchema;

class PolymorphicOneToManySchema extends RelationSchema
{
    public function __construct(
        protected string $originKey,
        protected string $originKeyTarget,
        protected string $foreignCollection,
        protected string $originTypeField,
        protected string $originTypeValue,
    ) {
        parent::__construct($foreignCollection, 'PolymorphicOneToMany');
    }

    public function setOriginKey(string $originKey): void
    {
        $this->originKey = $originKey;
    }

    public function getOriginKey(): string
    {
        return $this->originKey;
    }

    public function getForeignCollection(): string
    {
        return $this->foreignCollection;
    }

    public function getOriginTypeField(): string
    {
        return $this->originTypeField;
    }

    public function getOriginTypeValue(): string
    {
        return $this->originTypeValue;
    }
}
