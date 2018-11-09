<?php

namespace Fnash\GraphqlOnRestBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('fnash_graphql_on_rest');

        $rootNode
            ->children()
                ->booleanNode('debug')->defaultFalse()->end()
                ->arrayNode('servers')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('data_provider_id')->cannotBeEmpty()->end()
                            ->scalarNode('identifier_regex')->defaultValue('[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')->end()
                            ->scalarNode('resource_collection_key')->defaultValue('hydra:member')->end()
                            ->scalarNode('total_items_key')->defaultValue('hydra:totalItems')->end()
                            ->scalarNode('items_per_page_parameter_name')->defaultValue('limit')->end()
                            ->integerNode('maximum_items_per_page')->defaultValue(100)->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
