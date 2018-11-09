<?php

namespace Fnash\GraphqlOnRestBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Fnash\GraphqlOnRestBundle\DependencyInjection\Compiler\GuzzleDataCollectorPass;
use Fnash\GraphqlOnRestBundle\DependencyInjection\Compiler\QueryTypeResolversPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class FnashGraphqlOnRestBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new QueryTypeResolversPass());
        $container->addCompilerPass(new GuzzleDataCollectorPass());
    }
}
