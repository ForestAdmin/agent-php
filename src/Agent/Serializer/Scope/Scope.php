<?php

namespace ForestAdmin\AgentPHP\Agent\Serializer\Scope;

use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\NullResource;
use League\Fractal\Scope as FractalScope;
use function ForestAdmin\cache;

class Scope extends FractalScope
{
    /**
     * Execute the resources transformer and return the data and included data.
     *
     * @internal
     */
    protected function executeResourceTransformers(): array
    {
        $transformer = $this->resource->getTransformer();
        $data = $this->resource->getData();

        $transformedData = $includedData = [];

        if ($this->resource instanceof Item) {
            [$transformedData, $includedData[]] = $this->fireTransformer($transformer, $data);
        } elseif ($this->resource instanceof Collection) {
            foreach ($data as $value) {
                $transformer = cache('datasource')->getCollectionByClassName(get_class($value))->getTransformer();
                [$transformedData[], $includedData[]] = $this->fireTransformer(new $transformer(), $value);
            }
        } elseif ($this->resource instanceof NullResource) {
            $transformedData = null;
            $includedData = [];
        } else {
            throw new InvalidArgumentException(
                'Argument $resource should be an instance of League\Fractal\Resource\Item'
                .' or League\Fractal\Resource\Collection'
            );
        }

        return [$transformedData, $includedData];
    }
}
