<?php

namespace Fnash\GraphqlOnRestBundle\Command;

use Fnash\GraphqlOnRestBundle\GraphQL\GraphQLClient;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ValidateSchemaCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('graphql_on_rest:schema:validate')
            ->setDescription('Validates GraphQL schema.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getContainer()->get(GraphQLClient::class)->buildSchema()->assertValid();

        $output->writeln('<info>GraphQL Schema is valid.</info>');
    }
}
