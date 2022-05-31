<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations;

class OneToOneSchema extends SingleRelationSchema
{
    public function __construct(
        protected string $originKey,
        protected string $originKeyTarget,
        protected string $foreignCollection,
        protected string $type = 'OneToOne',
    ) {
        parent::__construct($originKey, $originKeyTarget);
    }
}
