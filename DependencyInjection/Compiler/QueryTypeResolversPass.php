<?php

namespace Fnash\GraphqlOnRestBundle\DependencyInjection\Compiler;

use Fnash\GraphqlOnRestBundle\GraphQL\GraphQLClient;
use Fnash\GraphqlOnRestBundle\GraphQL\TypeResolver\TypeResolverInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

/**
 * Class QueryTypeResolversPass.
 */
class QueryTypeResolversPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $typeResolvers = $container->findTaggedServiceIds('graphql_on_rest.type_resolver');

        $graphqlClientDefinition = $container->getDefinition(GraphQLClient::class);

        foreach ($typeResolvers as $id => $tags) {
            $tag = array_pop($tags);
            if (!isset($tag['server'])) {
                throw new RuntimeException(sprintf('The tag "graphql_on_rest.type_resolver" on the service "%s" must have attribute "server"', $id));
            }

            $serverKey = $tag['server'];

            $serviceClass = $container->findDefinition($id)->getClass();

            if (!in_array(TypeResolverInterface::class, class_implements($serviceClass))) {
                throw new RuntimeException(sprintf('The tagged service %s must implement %s', $serviceClass, TypeResolverInterface::class));
            }

            $graphqlClientDefinition->addMethodCall('addQueryTypeResolver', [
                new Reference($id),
                $serverKey
            ]);
        }
    }
}
