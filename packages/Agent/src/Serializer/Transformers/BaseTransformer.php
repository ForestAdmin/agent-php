<?php

namespace ForestAdmin\AgentPHP\Agent\Serializer\Transformers;

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyRelationSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\PolymorphicManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\PolymorphicOneToOneSchema;
use League\Fractal\Scope;
use League\Fractal\TransformerAbstract;

class BaseTransformer extends TransformerAbstract
{
    public function __construct(private string $name)
    {
    }

    /**
     * @param          $name
     * @param callable $callable
     * @return void
     */
    protected function addMethod($name, callable $callable): void
    {
        $this->$name = $callable;
    }

    /**
     * @param $method
     * @param $arguments
     * @return false|mixed
     */
    public function __call($method, $arguments)
    {
        return call_user_func_array($this->$method, $arguments);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function transform($data)
    {
        $forestCollection = AgentFactory::get('datasource')->getCollection($this->name);

        $relations = $forestCollection
            ->getFields()
            ->filter(fn ($field) => $field instanceof ManyToOneSchema || $field instanceof OneToOneSchema || $field instanceof PolymorphicManyToOneSchema || $field instanceof PolymorphicOneToOneSchema);

        foreach ($relations as $key => $value) {
            if (isset($data[$key]) && ! empty($data[$key]) &&
                (
                    ($value instanceof ManyRelationSchema && $data[$key][$value->getforeignKeyTarget()] !== null) ||
                    ($value instanceof OneToOneSchema && $data[$key][$value->getOriginKeyTarget()] !== null) ||
                    ($value instanceof PolymorphicManyToOneSchema) ||
                    ($value instanceof PolymorphicOneToOneSchema && $data[$key][$value->getOriginKeyTarget()] !== null)
                )
            ) {
                $this->defaultIncludes[] = $key;
            }
        }

        return $data;
    }

    public function processIncludedResources(Scope $scope, $data)
    {
        $includedData = [];

        $includes = $this->getDefaultIncludes();

        foreach ($includes as $include) {
            $relation = AgentFactory::get('datasource')->getCollection($this->name)->getFields()[$include];
            $item = $this->item($data[$include], new BaseTransformer($relation->getForeignCollection()), $relation->getForeignCollection());

            $includedData[$include] = [
                'data' => [
                    'type'               => $item->getResourceKey(),
                    'id'                 => $item->getData()['id'],
                    'attributes'         => $item->getData(),
                ],
            ];
        }

        return $includedData === [] ? false : $includedData;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
