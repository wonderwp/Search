<?php

use WonderWp\Component\PluginSkeleton\Exception\ServiceNotFoundException;
use WonderWp\Component\Search\Engine\SearchEngine;
use WonderWp\Component\Search\Engine\SearchEngineInterface;
use WonderWp\Component\Search\Renderer\SearchResultSetsRenderer;
use WonderWp\Component\Search\Result\SearchResult;
use WonderWp\Component\Search\ResultSet\SearchResultSet;
use WonderWp\Component\Service\ServiceInterface;
use WonderWp\Component\PluginSkeleton\ManagerInterface;
use WonderWp\Component\DependencyInjection\Container;
use WonderWp\Component\Search\Service\SearchServiceInterface;

add_action('wonderwp.loader.load', 'wwp_register_search_definitions_towards_container', 10, 2);
add_action('wwp.abstract_manager.run', 'wwp_register_search_service_towards_manager', 10, 2);

function wwp_register_search_definitions_towards_container(Container $container)
{
    $container['wwp.search.engine']   = function () {
        return new SearchEngine();
    };

    $container['wwp.search.renderer'] = function () {
        return new SearchResultSetsRenderer();
    };

    $container['wwp.search.result']   = $container->factory(function () {
        return new SearchResult();
    });

    $container['wwp.search.set']      = $container->factory(function () {
        return new SearchResultSet();
    });
}

function wwp_register_search_service_towards_manager(ManagerInterface $manager, Container $container)
{
    // Search
    try {
        $searchService = $manager->getService(ServiceInterface::SEARCH_SERVICE_NAME);
        if ($searchService instanceof SearchServiceInterface) {
            /** @var SearchEngineInterface $searchEngine */
            $searchEngine = $container['wwp.search.engine'];
            $searchEngine->addService($searchService);
        }
    } catch (ServiceNotFoundException $e) {
        //No search service found, nothing to do here
        if ($e->getServiceType() === ServiceInterface::SEARCH_SERVICE_NAME) {
            //No search service found, nothing to do here for now
        } else {
            throw $e;
        }
    }
}
