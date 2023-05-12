<?php

namespace ForestAdmin\AgentPHP\Agent\Services;

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Facades\ForestSchema;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Serializer\JsonApiSerializer;

use ForestAdmin\AgentPHP\Agent\Serializer\Transformers\BasicArrayTransformer;

use ForestAdmin\AgentPHP\Agent\Utils\Id as IdUtils;


use Illuminate\Support\Collection as BaseCollection;

use Illuminate\Support\Str;

use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;

class JsonApiResponse
{
    protected Manager $fractal;

    public function __construct()
    {
        $this->fractal = new Manager();
    }

    public function renderCollection($class, TransformerAbstract $transformer, string $name, Request $request)
    {
        $this->fractal->setSerializer(new JsonApiSerializer());
        $transformer->setAvailableIncludes(ForestSchema::getRelatedData($name));
        $resource = new Collection($class, $transformer, $name);

        if ($request->has('search')) {
            $resource->setMeta($this->searchDecorator($resource, $request->get('search')));
        }

        return $this->fractal->createData($resource)->toArray();
    }

    public function renderItem($data, TransformerAbstract $transformer, string $name)
    {
        $this->fractal->setSerializer(new JsonApiSerializer());
        $transformer->setAvailableIncludes(ForestSchema::getRelatedData($name));

        return $this->fractal->createData(new Item($data, $transformer, $name))->toArray();
    }

    public function renderChart($chart)
    {
        $data = [
            'id'         => Uuid::uuid4(),
            'type'       => 'stats',
            'attributes' => [
                'value' => $chart->serialize(),
            ],
        ];

        return $this->fractal->createData(new Item($data,  new BasicArrayTransformer(), 'stats'))->toArray();
    }

    public function deactivateCountResponse(): JsonResponse
    {
        return new JsonResponse(
            [
                'meta' => [
                    'count' => 'deactivated',
                ],
            ]
        );
    }

    protected function isCollection($instance): bool
    {
        return $instance instanceof BaseCollection;
    }

    protected function isPaginator($instance): bool
    {
        // todo
    }

    protected function searchDecorator(Collection $resource, string $searchValue): array
    {
        $forestCollection = AgentFactory::get('datasource')->getCollection($resource->getResourceKey());
        $decorator = ['decorators' => []];
        foreach ($resource->getData() as $key => $value) {
            $decorator['decorators'][$key]['id'] = IdUtils::packId($forestCollection, $value);
            foreach ($value as $fieldKey => $fieldValue) {
                if (! is_array($fieldValue) && Str::contains(Str::lower($fieldValue), Str::lower($searchValue))) {
                    $decorator['decorators'][$key]['search'][] = $fieldKey;
                }
            }
        }

        return $decorator;
    }
}
