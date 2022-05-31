<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations;

class OneToManySchema extends SingleRelationSchema
{
    public function __construct(
        protected string $originKey,
        protected string $originKeyTarget,
        protected string $foreignCollection,
        protected string $type = 'OneToMany',
    ) {
        parent::__construct($originKey, $originKeyTarget);
    }
}
