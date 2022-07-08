<?php

namespace ForestAdmin\AgentPHP\Agent\Serializer\Transformers;

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use Illuminate\Support\Str;
use League\Fractal\TransformerAbstract;

class BaseTransformer extends TransformerAbstract
{
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
     * @param $collection
     * @return mixed
     */
    public function transform($collection)
    {
        /*if (method_exists($model, 'handleSmartFields')) {
            $model->handleSmartFields()->handleSmartRelationships();
        }

        $relations = collect($model->getRelations())->filter()->all();
        $this->setDefaultIncludes(array_keys($relations));

        foreach ($relations as $key => $value) {
            $this->addMethod('include' . Str::ucfirst($key), fn() => $this->item($value, new ChildTransformer(), class_basename($value)));
        }*/

        dd($collection);

        return $collection->attributesToArray();
    }
}
