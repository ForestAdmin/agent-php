<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Contracts;

use ForestAdmin\AgentPHP\DatasourceToolkit\Caller;

interface CollectionContract
{
    public function getDataSource(): DatasourceContract;

    public function getName(): string;

    /**
     * @param Caller|null $caller
     * @param string      $name
     * @param array|null  $formValues
     * @param array|null  $filter TODO create Filter CLASS
     * @return array
     */
    public function getForm(?Caller $caller, string $name, ?array $formValues = null, ?array $filter = null): array;

    /* TODO getSchema(): CollectionSchema; */
    /* TODO execute */





    /*
     *   get dataSource(): DataSource;
  get name(): string;
  get schema(): CollectionSchema;

  execute(
    caller: Caller,
    name: string,
    formValues: RecordData,
    filter?: Filter,
  ): Promise<ActionResult>;

  getForm(
    caller: Caller,
    name: string,
    formValues?: RecordData,
    filter?: Filter,
  ): Promise<ActionField[]>;

  create(caller: Caller, data: RecordData[]): Promise<RecordData[]>;

  list(caller: Caller, filter: PaginatedFilter, projection: Projection): Promise<RecordData[]>;

  update(caller: Caller, filter: Filter, patch: RecordData): Promise<void>;

  delete(caller: Caller, filter: Filter): Promise<void>;

  aggregate(
    caller: Caller,
    filter: Filter,
    aggregation: Aggregation,
    limit?: number,
  ): Promise<AggregateResult[]>;
     */
}
