<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Context;

use ForestAdmin\AgentPHP\Agent\Facades\Logger;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Context\CollectionCustomizationContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\ActionCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Record;
use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\ProjectionValidator;

class ActionContext extends CollectionCustomizationContext
{
    public function __construct(
        ActionCollection $collection,
        ?Caller $caller,
        protected PaginatedFilter $filter,
        protected array $formValues = [],
        protected array $used = [],
        protected ?string $changeField = null
    ) {
        parent::__construct($collection, $caller);
    }

    /**
     * @return PaginatedFilter
     */
    public function getFilter(): PaginatedFilter
    {
        return $this->filter;
    }

    /**
     * @return array
     */
    public function getFormValues(): array
    {
        return $this->formValues;
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
        ProjectionValidator::validate($this->realCollection, $fields);

        return $this->realCollection->list($this->caller, $this->filter, $fields);
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
        $projection = (new Projection())->withPks($this->realCollection);
        $records = $this->getRecords($projection);

        return collect($records)->map(fn ($record) => Record::getPrimaryKeys($this->realCollection, $record))->toArray();
    }

    public function getUsed(): array
    {
        return $this->used;
    }

    /**
     * @return string|null
     * @deprecated use `hasFieldChange` instead.
     */
    public function getChangeField(): ?string
    {
        Logger::log(
            'warn',
            'Usage of `getChangeField` is deprecated, please use `hasFieldChanged` instead.'
        );

        return $this->changeField;
    }

    public function hasFieldChanged(string $fieldName): bool
    {
        $this->used[$fieldName] = $fieldName;

        return $this->changeField === $fieldName;
    }
}
