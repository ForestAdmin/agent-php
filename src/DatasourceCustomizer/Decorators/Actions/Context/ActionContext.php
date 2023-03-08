<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Context;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\ActionCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Record;
use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\ProjectionValidator;

class ActionContext
{
    public function __construct(
        protected ActionCollection $collection,
        protected Caller $caller,
        protected PaginatedFilter $filter,
        protected array $formValues = [],
        protected array $used = []
    ) {
    }

    public function getFormValue(string $key)
    {
        $this->used[$key] = $key;

        return $this->formValues[$key] ?? null;
    }

    /**
     * Get all the records selected by an action
     * @param fields An array of fields needed in the response
     * @example
     * .getRecords(['id', 'isActive', 'name']);
     */
    public function getRecords(array|Projection $fields): array
    {
        if (is_array($fields)) {
            $fields = new Projection($fields);
        }
        ProjectionValidator::validate($this->collection, $fields);

        return $this->collection->list($this->caller, $this->filter, $fields);
    }

    /**
     * Get all the records ids selected by an action
     */
    public function getRecordIds(): array
    {
        $compositeIds = $this->getCompositeRecordIds();

        return collect($compositeIds)->map(fn ($id) => $id[0])->toArray();
    }

  /**
   * Get all the records ids (when the collection uses composite keys)
   */
  public function getCompositeRecordIds(): array
  {
      $projection = (new Projection())->withPks($this->collection);
      $records = $this->getRecords($projection);

      return collect($records)->map(fn ($record) => Record::getPrimaryKeys($this->collection, $record))->toArray();
  }
}
