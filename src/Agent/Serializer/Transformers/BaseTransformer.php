<?php

namespace ForestAdmin\AgentPHP\Agent\Serializer\Transformers;

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Serializer\DataTypes;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;
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
            ->filter(fn ($field) => $field instanceof ManyToOneSchema || $field instanceof OneToOneSchema);

        foreach ($relations as $key => $value) {
            if (isset($data[$key])) {
                $this->defaultIncludes[] = $key;
                $this->addMethod(
                    'include' . Str::ucfirst($key),
                    fn () => $this->item($data[$key], new BaseTransformer($value->getForeignCollection()), $value->getForeignCollection())
                );
            } else {
                $this->addMethod('include' . Str::ucfirst($key), fn () => $this->null());
            }
            unset($data[$key]);
        }

        $fields = $forestCollection->getFields();
        foreach ($data as $key => &$value) {
            /** @var ColumnSchema $columnSchema */
            $columnSchema = $fields[$key];
            $value = $this->renderValue($columnSchema->getColumnType(), $value);
        }



        return $data;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    private function renderValue(string $originalType, $content)
    {
        return match ($originalType) {
            PrimitiveType::DATEONLY => $content->format('Y-m-d'),
            PrimitiveType::DATE     => $content->format('Y-m-d H:i:s'),
            PrimitiveType::TIMEONLY => $content->format('H:i:s'),
            default                 => $content,
        };
    }
}
