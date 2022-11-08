<?php

namespace ForestAdmin\AgentPHP\Agent\Services;

use ForestAdmin\AgentPHP\Agent\Facades\ForestSchema;
use ForestAdmin\AgentPHP\Agent\Serializer\JsonApiSerializer;

use ForestAdmin\AgentPHP\Agent\Serializer\Transformers\BasicArrayTransformer;

use function ForestAdmin\config;

use Illuminate\Support\Collection as BaseCollection;

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

    public function renderCollection($class, TransformerAbstract $transformer, string $name)
    {
        $this->fractal->setSerializer(new JsonApiSerializer(config('appUrl')));
        $transformer->setAvailableIncludes(ForestSchema::getRelatedData($name));

        return $this->fractal->createData(new Collection($class, $transformer, $name))->toArray();
    }

    public function renderItem($data, TransformerAbstract $transformer, string $name)
    {
        $this->fractal->setSerializer(new JsonApiSerializer(config('appUrl')));

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

    protected function searchDecorator(BaseCollection $items, $searchValue): array
    {
        // todo
    }
}
