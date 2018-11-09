<?php

namespace Fnash\GraphqlOnRestBundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;

class OperationCollector implements DataCollectorInterface
{
    private $operations = [];

    public function addOperation(string $source, $variables, $data)
    {
        $this->operations[] = [
            'source' => $source,
            'variables' => $variables,
            'data' => $data,
        ];
    }

    public function getOperations()
    {
        return $this->operations;
    }

    /**
     * Collects data for the given Request and Response.
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
    }

    /**
     * Returns the name of the collector.
     *
     * @return string The collector name
     */
    public function getName()
    {
        return 'graphql_on_rest.graphql_collector';
    }

    public function reset()
    {
        $this->operations = [];
    }
}
