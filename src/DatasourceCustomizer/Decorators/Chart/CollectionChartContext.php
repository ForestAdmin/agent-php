<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Chart;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Context\CollectionCustomizationContext;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

class CollectionChartContext extends CollectionCustomizationContext
{
    public function __construct(
        ChartCollection $collection,
        Caller $caller,
        protected array $compositeRecordId
    ) {
        parent::__construct($collection, $caller);
    }

    /**
     * @return array
     */
    public function getCompositeRecordId(): array
    {
        return count($this->compositeRecordId) > 1 ?
            throw new ForestException("Collection is using a composite pk: use 'context.compositeRecordId'.")
            : $this->compositeRecordId[0];
    }

    public function getRecord(array|Projection $fields): array
    {
        $conditionTree = ConditionTreeFactory::matchIds($this->realCollection, [$this->compositeRecordId]);
        if (is_array($fields)) {
            $fields = new Projection($fields);
        }

        return $this->getCollection()->list(new Filter($conditionTree), $fields);
    }
}
