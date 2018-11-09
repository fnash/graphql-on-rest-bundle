<?php

namespace Fnash\GraphqlOnRestBundle\GraphQL;

use Fnash\GraphqlOnRestBundle\DataCollector\OperationCollector;
use Fnash\GraphqlOnRestBundle\GraphQL\TypeResolver\ListTypeResolverFactory;
use Fnash\GraphqlOnRestBundle\GraphQL\TypeResolver\TypeResolver;
use Fnash\GraphqlOnRestBundle\GraphQL\TypeResolver\TypeResolverInterface;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Schema;
use GraphQL\Utils\SchemaPrinter;
use GraphQL\Validator\Rules;
use Symfony\Component\Stopwatch\Stopwatch;
use GraphQL\Error\InvariantViolation;

/**
 * Class GraphQLClient.
 */
class GraphQLClient
{
    /**
     * @var
     */
    private $debug;

    /**
     * @var Guzzle Collector
     */
    private $guzzleCollector;

    /**
     * @var OperationCollector
     */
    private $OperationCollector;

    public function __construct($debug = false, OperationCollector $OperationCollector = null, $guzzleCollector = null)
    {
        $this->debug = (bool) $debug;
        $this->OperationCollector = $OperationCollector;
        $this->guzzleCollector = $guzzleCollector;
    }

    /**
     * @var array
     */
    private $queryTypeResolvers = [];

    /**
     * @var array
     */
    private $listTypeResolverFactories = [];

    /**
     * @param TypeResolverInterface $typeResolver
     * @param string $key
     *
     * @return GraphQLClient
     */
    public function addQueryTypeResolver(TypeResolverInterface $typeResolver, string $key): GraphQLClient
    {
        $this->queryTypeResolvers[$key][] = $typeResolver;

        return $this;
    }

    /**
     * @param ListTypeResolverFactory $listTypeResolverFactory
     * @param string $key
     *
     * @return GraphQLClient
     */
    public function addListTypeResolverFactory(ListTypeResolverFactory $listTypeResolverFactory, string $key)
    {
        $this->listTypeResolverFactories[$key] = $listTypeResolverFactory;

        return $this;
    }

    /**
     * @param string     $queryString
     * @param array|null $variables
     * @param mixed|null $context
     * @param bool|null  $debug
     *
     * @return array
     *
     * @throws \Exception
     */
    public function query(string $queryString, array $variables = null, $context = null, $debug = null)
    {
        if (null !== $debug) {
            $this->debug = (bool) $debug;
        }

        if ($this->debug) {
            $stopwatch = new Stopwatch();
            $stopwatch->start('GraphQLQuery');
        }

        $result = GraphQL::executeQuery(
            $this->buildSchema(),
            $queryString,
            $rootValue = null,
            $context,
            $variables = $this->buildVariableValues($variables),
            $operationName = null,
            $fieldResolver = null,
            $this->getValidationRules()
        );

        if ($this->debug) {
            $event = $stopwatch->stop('GraphQLQuery');

            $calls = [];

            $httpTime = 0;
            if ($this->guzzleCollector) {
                foreach ($this->guzzleCollector->getHistory() as $request) {
                    $transaction = $this->guzzleCollector->getHistory()[$request];
                    $totalTime = $transaction['info']['total_time'] ?? null;
                    $totalTime *= 1000;
                    $httpTime += $totalTime;

                    $calls[] = [
                        'url' => $request->getUri()->getPath().'?'.urldecode($request->getUri()->getQuery()),
                        'duration_ms' => $totalTime,
                    ];
                }
            }

            $result->extensions = $data = [
                'http_calls' => $calls,
                'query' => [
                    'duration_ms' => $queryDuration = $event->getDuration(),
                    'duration_no_http_ms' => $queryDuration - $httpTime,
                ],
            ];

            if ($this->OperationCollector) {
                $this->OperationCollector->addOperation($queryString, $variables, $data);
            }
        }

        return $result->toArray($this->debug);
    }

    /**
     * prints Schema in GraphQL type language.
     *
     * @return string
     */
    public function printSchema()
    {
        return SchemaPrinter::doPrint($this->buildSchema());
    }

    /**
     * @return Schema
     */
    public function buildSchema()
    {
        $fields = [];

        foreach ($this->queryTypeResolvers as $key => $queryTypeResolvers) {
            /* @var $queryTypeResolver TypeResolverInterface */
            foreach ($queryTypeResolvers as $queryTypeResolver) {
                $fields[$queryTypeResolver->getName()] = $queryTypeResolver->getQueryFieldConfig();

                $factory = $this->listTypeResolverFactories[$key];
                $fields[$factory->getListTypeName($queryTypeResolver)] = $factory->getQueryFieldConfig($queryTypeResolver);
            }
        }

        $queryType = new ObjectType([
            'name' => 'Query',
            'fields' => $fields,
        ]);

        $schema = new Schema([
            'query' => $queryType,
            'types' => [],
        ]);

        if (!$this->debug) {
            try {
                $schema->assertValid();
            } catch (InvariantViolation $e) {
                throw $e;
            }
        }

        return $schema;
    }

    /**
     * @param $variables
     *
     * @return array|null
     */
    private function buildVariableValues($variables)
    {
        $variableValues = null;

        if (is_array($variables) && count($variables)) {
            $variableValues = [];

            // take array and bool variables as they are
            foreach ($variables as $keyVariable => $variable) {
                if (is_array($variable) || is_bool($variable)) {
                    $variableValues[TypeResolver::queryParamToArgumentName($keyVariable)] = $variable;
                    unset($variables[$keyVariable]);
                }
            }

            // transform other variables to valid GraphQL arguments
            $queryParamStrings = explode('&', http_build_query($variables));

            foreach ($queryParamStrings as $queryParamString) {
                $queryParamArray = explode('=', $queryParamString);
                if (count($queryParamArray) >= 2 && strlen($varValue = urldecode($queryParamArray[1]))) {
                    $variableValues[TypeResolver::queryParamToArgumentName(urldecode($queryParamArray[0]))] = $varValue;
                }
            }
        }

        return $variableValues;
    }

    /**
     * @return array|null
     */
    private function getValidationRules()
    {
        if ($this->debug) {
            return;
        }

        return array_merge(
            GraphQL::getStandardValidationRules(),
            [
                new Rules\DisableIntrospection(),
            ]
        );
    }
}
