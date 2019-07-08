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

        $servers = $container->getParameter('graphql_on_rest.servers');

        foreach ($typeResolvers as $id => $tags) {
            $tag = array_pop($tags);
            if (isset($tag['server'])) {
                if (!in_array($tag['server'], $servers)) {
                    throw new RuntimeException(sprintf('The attribute "server" of the tag "graphql_on_rest.type_resolver" (service "%s") must be in [ %s ]', $id, implode(', ', $servers)));
                } else {
                    $serverKey = $tag['server'];
                }

            }  else {
                if (count($servers) === 1) {
                    $serverKey = $servers[0];
                } else {
                    throw new RuntimeException(sprintf('The tag "graphql_on_rest.type_resolver" on the service "%s" must have attribute "server"', $id));
                }
            }

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
