<?php

namespace ForestAdmin\AgentPHP\Agent\Services;

use ForestAdmin\AgentPHP\Agent\Facades\ForestSchema;
use ForestAdmin\AgentPHP\Agent\Serializer\JsonApiSerializer;
use Illuminate\Support\Collection as BaseCollection;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use Symfony\Component\HttpFoundation\JsonResponse;
use function ForestAdmin\config;

class JsonApiResponse
{
    protected Manager $fractal;

    public function __construct()
    {
        $this->fractal = new Manager();
    }

    public function render($class, TransformerAbstract $transformer, string $name, array $meta = [])
    {
        $this->fractal->setSerializer(new JsonApiSerializer(config('agentUrl')));
        //$transformer = new BaseTransformer();
        // eventuellement regarder de quel type est le 1er élément pour appeler le bon transformer.
        // ds le dossier transformers rajouté autant de transformer que de type (Model Laravel, Entity Symfony, array simple,...)
        //
        // séparé les transformers ds des datasources différent ?
        //dd(cache('datasource'));
        //$resource = new Collection($class, $transformer, $name);

        /*$transformer = app()->make(BaseTransformer::class);*/
        $transformer->setAvailableIncludes(ForestSchema::getRelatedData($name));

        if (is_array($class) || $this->isCollection($class)) {
            $resource = new Collection($class, $transformer, $name);
        } /*elseif ($this->isPaginator($class)) {
            $resource = new Collection($class->getCollection(), $transformer, $name);
            if (request()->has('search')) {
                $resource->setMeta($this->searchDecorator($resource->getData(), request()->get('search')));
            }
        }*/ else {
            $resource = new Item($class, $transformer, $name);
        }

        /*if ($meta) {
            $resource->setMeta(array_merge($resource->getMeta(), $meta));
        }*/

        return $this->fractal->createData($resource)->toArray();
    }

    public function renderItem($data, TransformerAbstract $transformer, string $name)
    {
        $this->fractal->setSerializer(new JsonApiSerializer(config('agentUrl')));
        $resource = new Item($data, $transformer, $name);

        return $this->fractal->createData($resource)->toArray();
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
