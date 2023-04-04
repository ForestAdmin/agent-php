<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Actions;

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractAuthenticatedRoute;
use ForestAdmin\AgentPHP\Agent\Utils\ContextFilterFactory;
use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\ForestActionValueConverter;
use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\GeneratorAction;
use ForestAdmin\AgentPHP\Agent\Utils\Id;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\BaseAction;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\ActionScope;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\FilterFactory;
use Illuminate\Support\Str;

class Actions extends AbstractAuthenticatedRoute
{
    protected BaseAction $action;

    protected int $index;

    public function __construct(protected CollectionContract $collection, protected string $actionName)
    {
        $this->action = $this->collection->getActions()->get($this->actionName);
        $this->index = $collection->getActions()->keys()->search($this->actionName);
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
            fn ($args) => $this->handleHookRequest($args)
        );

        $this->addRoute(
            $routeName . '.change',
            'post',
            $path . '/hooks/change',
            fn ($args) => $this->handleHookRequest($args)
        );

        return $this;
    }

    public function handleRequest(array $args = []): array
    {
        $this->build($args);
        $scope = $this->permissions->getScope($this->collection);
        $filter = ContextFilterFactory::buildPaginated($this->collection, $this->request, $scope);

        return $this->collection->execute($this->caller, $this->actionName, $this->request->get('data'), $filter);
    }

    public function handleHookRequest(array $args = []): array
    {
        $this->build($args);

        $forestFields = $this->request->input('data.attributes.fields');
        $data = $forestFields !== null ? ForestActionValueConverter::makeFormDataFromFields(AgentFactory::get('datasource'), $forestFields) : null;
        $filter = $this->getRecordSelection();
        $fields = $this->collection->getForm($this->caller, $this->actionName, $data, $filter);

        return ['content' => ['fields' => collect($fields)->map(fn ($field) => GeneratorAction::buildFieldSchema(AgentFactory::get('datasource'), $field))]];
    }

    private function getRecordSelection(bool $includeUserScope = true): Filter
    {
        // Match user filter + search + scope? + segment.
        $scope = $includeUserScope ? $this->permissions->getScope($this->collection) : null;
        $filter = ContextFilterFactory::buildPaginated($this->collection, $this->request, $scope);

        // Restrict the filter to the selected records for single or bulk actions.
        if ($this->action->getScope() === ActionScope::GLOBAL) {
            $selectionIds = Id::parseSelectionIds($this->collection, $this->request);
            $selectedIds = ConditionTreeFactory::matchIds($this->collection, $selectionIds['ids']);
            if ($selectionIds['areExcluded']) {
                $selectedIds->inverse();
            }

            $filter = $filter->override(conditionTree: ConditionTreeFactory::intersect([$filter->getConditionTree(), $selectedIds]));
        }

        if ($relation = $this->request->input('data.attributes.parent_association_name')) {
            $parent = AgentFactory::get('datasource')->getCollection($this->request->input('data.attributes.parent_collection_name'));
            $parentId = Id::unpackId($parent, $this->request->input('data.attributes.parent_collection_id'));
            $filter = FilterFactory::makeForeignFilter($parent, $parentId, $relation, $this->caller, $filter);
        }

        return $filter;
    }
}
