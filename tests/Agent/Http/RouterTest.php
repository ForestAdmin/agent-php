<?php

use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Http\Router;
use ForestAdmin\AgentPHP\Agent\Routes\Capabilities\Collections;
use ForestAdmin\AgentPHP\Agent\Routes\Charts\Charts;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Count;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Destroy;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Listing;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\NativeQuery;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Related\AssociateRelated;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Related\CountRelated;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Related\DissociateRelated;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Related\ListingRelated;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Related\UpdateRelated;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Show;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Store;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\Update;
use ForestAdmin\AgentPHP\Agent\Routes\Security\Authentication;
use ForestAdmin\AgentPHP\Agent\Routes\Security\ScopeInvalidation;
use ForestAdmin\AgentPHP\Agent\Routes\System\HealthCheck;
use ForestAdmin\AgentPHP\DatasourceCustomizer\CollectionCustomizer;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\BaseAction;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\ActionScope;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;

test('getRoutes() should work', function () {
    $datasource = new Datasource();
    $collectionBook = new Collection($datasource, 'Book');
    $datasource->addCollection($collectionBook);
    $this->buildAgent(new Datasource());
    $this->agent->addDatasource($datasource);

    $config = Cache::get('config');
    unset($config['envSecret']);
    Cache::put('config', $config, 3600);

    $this->agent->addChart('myChart', fn () => true);
    $this->agent->customizeCollection(
        'Book',
        fn (CollectionCustomizer $builder) => $builder
            ->addChart('myCollectionChart', fn () => true)
            ->addAction('myAction', new BaseAction(
                ActionScope::SINGLE,
                fn ($context, $responseBuilder) => $responseBuilder->success('BRAVO !!!!')
            ))
    );
    $this->agent->build();

    $reflection = new \ReflectionClass(Router::class);
    $getApiChartsRoutesMethod = $reflection->getMethod('getApiChartsRoutes');
    $chartRoutes = call_user_func($getApiChartsRoutesMethod->getClosure());
    $getActionsRoutesMethod = $reflection->getMethod('getActionsRoutes');
    $actionRoutes = call_user_func($getActionsRoutesMethod->getClosure());

    expect(Router::getRoutes())
        ->toEqual(
            array_merge(
                $actionRoutes,
                $chartRoutes,
                HealthCheck::make()->getRoutes(),
                Authentication::make()->getRoutes(),
                Charts::make()->getRoutes(),
                ScopeInvalidation::make()->getRoutes(),
                Listing::make()->getRoutes(),
                Store::make()->getRoutes(),
                Count::make()->getRoutes(),
                Show::make()->getRoutes(),
                Update::make()->getRoutes(),
                Destroy::make()->getRoutes(),
                ListingRelated::make()->getRoutes(),
                UpdateRelated::make()->getRoutes(),
                AssociateRelated::make()->getRoutes(),
                DissociateRelated::make()->getRoutes(),
                CountRelated::make()->getRoutes(),
                Collections::class::make()->getRoutes(),
                NativeQuery::make()->getRoutes()
            )
        );
});
