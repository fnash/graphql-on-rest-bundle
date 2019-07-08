<?php

namespace Fnash\GraphqlOnRestBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Fnash\GraphqlOnRestBundle\DataCollector\OperationCollector;
use Fnash\GraphqlOnRestBundle\GraphQL\DataLoader\IriDataLoader;
use Fnash\GraphqlOnRestBundle\GraphQL\GraphQLClient;
use Fnash\GraphqlOnRestBundle\GraphQL\TypeResolver\ListTypeResolverFactory;
use Fnash\GraphqlOnRestBundle\GraphQL\TypeResolver\TypeResolver;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class FnashGraphqlOnRestExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $configuration = $this->processConfiguration($configuration, $configs);

        $graphqlClientDefinition = $this->createClientDefinition($configuration, $container);

        foreach ($configuration['servers'] as $serverName => $serverConfig) {

            $dataProviderServiceId = $serverConfig['data_provider_id'];
            $iriDataLoaderServiceId = 'graphql_on_rest.iri_data_loader.'.$serverName;
            $container->setDefinition($iriDataLoaderServiceId, new Definition(IriDataLoader::class, [
                new Reference($dataProviderServiceId),
                (string) $serverConfig['items_per_page_parameter_name'],
                (int) $serverConfig['maximum_items_per_page'],
                (string) $serverConfig['identifier_regex'],
            ]));

            $container->setDefinition('graphql_on_rest.type_resolver.'.$serverName, new Definition(TypeResolver::class, [
                new Reference($dataProviderServiceId),
                new Reference($iriDataLoaderServiceId),
            ]));

            $listTyperesolverFactoryServiceId = 'graphql_on_rest.list_type_resolver_factory.'.$serverName;
            $container->setDefinition($listTyperesolverFactoryServiceId, new Definition(ListTypeResolverFactory::class, [
                $serverConfig['resource_collection_key'],
                $serverConfig['total_items_key'],
            ]));

            $graphqlClientDefinition->addMethodCall('addListTypeResolverFactory', [
                new Reference($listTyperesolverFactoryServiceId),
                $serverName
            ]);
        }

        $container->setParameter('graphql_on_rest.servers', array_keys($configuration['servers']));

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }

    /**
     * @param array $configuration
     * @param ContainerBuilder $container
     *
     * @return Definition
     */
    private function createClientDefinition(array $configuration, ContainerBuilder $container)
    {
        $graphqlClientDefinition = new Definition(GraphQLClient::class, [
            $configuration['debug'],
            new Reference(OperationCollector::class),
            null
        ]);

        $container->setDefinition(GraphQLClient::class, $graphqlClientDefinition);

        return $graphqlClientDefinition;
    }
}
