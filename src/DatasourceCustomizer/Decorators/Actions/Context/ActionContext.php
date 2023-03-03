<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Context;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\ActionCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;

class ActionContext
{
    private array $queries = [];

    private Projection $projection;

    public function __construct(
        protected ActionCollection $collection,
        protected Caller $caller,
        protected ?array $formValues,
        protected ?Filter $filter = null,
        protected array $used = []
    ) {
        $this->projection = new Projection();
    }

    public function getFormValue(string $key)
    {
        $this->used[$key] = $key;

        return $this->formValues[$key] ?? null;
    }

//  /**
//   * Get all the records selected by an action
//   * @param fields An array of fields needed in the response
//   * @example
//   * .getRecords(['id', 'isActive', 'name']);
//   */
//  async getRecords(fields: TFieldName<S, N>[]): Promise<TRow<S, N>[]> {
//    // This function just queues the request into this.queries, so that we can merge all calls
//    // to getRecords() into a single one.
//
//    // The call to setTimeout which resolve the promises will trigger only once all handlers in
//    // the customer's form have been called as Promises are queued before calls to setTimeout
//    // in Node.js event loop
//
//    // @see https://dev.to/khaosdoctor/node-js-under-the-hood-3-deep-dive-into-the-event-loop-135d\
//    //   #microtasks-and-macrotasks
//    //   Ordering of micro/macro tasks in Node.js event loop
//    //
//    // @see https://github.com/graphql/dataloader
//    //   A library from facebook from which this pattern is inspired.
//
//    ProjectionValidator.validate(this.realCollection, fields);
//
//    const deferred = new Deferred<TRow<S, N>[]>();
//    const projection = new Projection(...fields);
//
//    if (this.queries.length === 0) setTimeout(() => this.runQuery());
//    this.queries.push({ projection, deferred });
//    this.projection = this.projection.union(projection);
//
//    return deferred.promise;
//  }
//
//  /**
//   * Get all the records ids selected by an action
//   */
//  async getRecordIds(): Promise<Array<string | number>> {
//    const compositeIds = await this.getCompositeRecordIds();
//
//    return compositeIds.map(id => id[0]);
//  }
//
//  /**
//   * Get all the records ids (when the collection uses composite keys)
//   */
//  async getCompositeRecordIds(): Promise<CompositeId[]> {
//    const projection = new Projection().withPks(this.realCollection) as string[];
//    const records = await this.getRecords(projection as TFieldName<S, N>[]);
//
//    return records.map(r => RecordUtils.getPrimaryKey(this.realCollection.schema, r));
//  }
//
//  private async runQuery(): Promise<void> {
//    const { queries, projection } = this;
//    this.reset();
//
//    try {
//        // Run a single query which contains all fields / relations which were requested by
//        // the different calls made to getRecords
//        const records = await this.collection.list(
//            this.filter,
//            projection as string[] as TFieldName<S, N>[],
//      );
//
//      // Resolve each on of the promises only with the requested fields.
//      for (const query of queries) query.deferred.resolve(query.projection.apply(records));
//    } catch (e) {
//        // Rejecting each promises at next tick
//
//        // This ensures that we don't let any promise hanging forever if the customer throws in
//        // the rejection handler.
//        for (const query of queries) {
//            process.nextTick(() => query.deferred.reject(e));
//      }
//    }
//  }
//
//  private reset(): void {
//    this.queries = [];
//    this.projection = new Projection();
//  }
}
