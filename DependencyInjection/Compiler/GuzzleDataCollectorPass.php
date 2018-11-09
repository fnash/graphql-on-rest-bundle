<?php

namespace Fnash\GraphqlOnRestBundle\DependencyInjection\Compiler;

use Fnash\GraphqlOnRestBundle\GraphQL\GraphQLClient;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class GuzzleDataCollectorPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->has('csa_guzzle.data_collector.guzzle')) {
            $definition = $container->getDefinition(GraphQLClient::class);
            $definition->replaceArgument(2, new Reference('csa_guzzle.data_collector.guzzle'));
        }
    }
}
