<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Actions;

use ForestAdmin\AgentPHP\Agent\Routes\AbstractAuthenticatedRoute;
use ForestAdmin\AgentPHP\Agent\Utils\ContextFilterFactory;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\BaseAction;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use Illuminate\Support\Str;

class Actions extends AbstractAuthenticatedRoute
{
    protected BaseAction $action;

    protected int $index;

    public function __construct(protected CollectionContract $collection, protected string $actionName)
    {
        $this->action = $this->collection->getActions()->get($this->actionName);
        $this->index = $collection->getActions()->keys()->search($actionName);
        parent::__construct();
        $this->setupRoutes();
    }

    /**
     * @return $this
     */
    public function setupRoutes(): self
    {
        $slug = Str::slug($this->actionName);
        $path = '/_actions/' . $this->collection->getName() . '/' . $this->index . '/' . $slug;
        $routeName = 'forest.action.' . $this->collection->getName() . '.' . $this->index . '.' . $slug;

        $this->addRoute(
            $routeName,
            'post',
            $path,
            fn ($args) => $this->handleRequest($args)
        );

        $this->addRoute(
            $routeName . '.load',
            'post',
            $path . '/hooks/load',
            fn ($args) => $this->handleHookLoadRequest($args)
        );

        $this->addRoute(
            $routeName . '.change',
            'post',
            $path . '/hooks/change',
            fn ($args) => $this->handleHookChangeRequest($args)
        );

        return $this;
    }

    public function handleRequest(array $args = []): array
    {
        $this->build($args);
        $scope = $this->permissions->getScope($this->collection);
        $filter = ContextFilterFactory::buildPaginated($this->collection, $this->request, $scope);
        //(Caller $caller, string $name, array $data, ?Filter $filter = null)

        return $this->collection->execute($this->caller, $this->actionName, $this->request->get('data'), $filter);

//        $this->build($args);
//        $this->permissions->canChart($this->request);
//        $scope = $this->permissions->getScope($this->collection);
//        $this->filter = ContextFilterFactory::build($this->collection, $this->request, $scope);
//        $this->setType($this->request->get('type'));
//        $this->setCaller(QueryStringParser::parseCaller($this->request));
//
//        return ['content' => JsonApi::renderChart($this->{'make' . $this->type}())];
    }

    public function handleHookLoadRequest(array $args = []): array
    {
        return ['content' => 1];
    }

    public function handleHookChangeRequest(array $args = []): array
    {
        return ['content' => 1];
    }
}
