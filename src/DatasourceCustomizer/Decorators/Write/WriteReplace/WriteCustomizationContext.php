<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Write\WriteReplace;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Context\CollectionCustomizationContext;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;

class WriteCustomizationContext extends CollectionCustomizationContext
{
    public function __construct(CollectionContract $collection, Caller $caller, protected string $action, protected array $record)
    {
        parent::__construct($collection, $caller);
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getRecord(): array
    {
        return $this->record;
    }

//readonly action: 'update' | 'create';
//  readonly record: TPartialSimpleRow<S, N>;
//
//  constructor(
//    collection: Collection,
//    caller: Caller,
//    action: 'update' | 'create',
//    record: TPartialSimpleRow<S, N>,
//  ) {
//    super(collection, caller);
//
//    this.action = action;
//    this.record = Object.freeze({ ...record });
//  }
}
