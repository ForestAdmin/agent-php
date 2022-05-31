<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations;

class ManyToManySchema extends ManyRelationSchema
{
    public function __construct(
        protected string $foreignKey,
        protected string $foreignKeyTarget,
        protected string $throughCollection,
        protected string $originKey,
        protected string $originKeyTarget,
        protected string $type = 'ManyToMany',
    ) {
        parent::__construct($foreignKey, $foreignKeyTarget);
    }
}
