<?php

namespace ForestAdmin\AgentPHP\Agent\Services;


class JsonApiResponse
{

//    protected Manager $fractal;

    public function __construct()
    {
//        $this->fractal = app()->make(Manager::class);
    }

    public function render($class, string $name, array $meta = [])
    {
        // todo
    }

    public function renderItem($data, string $name, string $transformer)
    {
        // todo
    }

    public function deactivateCountResponse(): JsonResponse
    {
        // todo
    }

    protected function isCollection($instance): bool
    {
        // todo
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
