<?php

namespace ForestAdmin\AgentPHP\Agent\Serializer\Transformers;

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyRelationSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\PolymorphicManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\PolymorphicOneToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Collection as CollectionUtils;
use Illuminate\Support\Str;
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

                if ($value instanceof PolymorphicManyToOneSchema) {
                    $foreignCollection = AgentFactory::get('datasource')->getCollection(CollectionUtils::fullNameToSnakeCase($data[$value->getForeignKeyTypeField()]));
                    $this->addMethod(
                        'include' . Str::ucfirst(Str::camel($key)),
                        fn () => $this->item($data[$key], new BaseTransformer($foreignCollection->getName()), $foreignCollection->getName())
                    );
                } else {
                    $this->addMethod(
                        'include' . Str::ucfirst(Str::camel($key)),
                        fn () => $this->item($data[$key], new BaseTransformer($value->getForeignCollection()), $value->getForeignCollection())
                    );
                }
            } else {
                $this->addMethod('include' . Str::ucfirst(Str::camel($key)), fn () => $this->null());
            }
            unset($data[$key]);
        }

        return $data;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
