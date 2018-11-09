<?php

namespace Fnash\GraphqlOnRestBundle\GraphQL\TypeResolver;

use Fnash\GraphqlOnRestBundle\GraphQL\DataLoader\IriDataLoader;
use Fnash\GraphqlOnRestBundle\GraphQL\DataProvider\DataProviderInterface;
use Fnash\GraphqlOnRestBundle\GraphQL\Type\JsonLdObjectType;
use GraphQL\Deferred;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

abstract class TypeResolver implements TypeResolverInterface
{
    const BRACKET_OPEN = '[';
    const BRACKET_CLOSE = ']';
    const DOT = '.';

    const GEN_BRACKET_OPEN = '_br_op_';
    const GEN_BRACKET_CLOSE = '_br_cl_';
    const GEN_DOT = '_dot_';

    /**
     * @var array
     */
    protected $argumentsConfig = [];

    /**
     * @var DataProviderInterface
     */
    protected $dataProvider;

    /**
     * @var IriDataLoader
     */
    protected $iriDataLoader;

    /**
     * @var string
     */
    protected $name;

    /**
     * {@inheritdoc}
     */
    abstract public function getQueryFieldConfig(): array;

    /**
     * TypeResolver constructor.
     *
     * @param DataProviderInterface $dataProvider
     * @param IriDataLoader         $iriDataLoader
     */
    public function __construct(DataProviderInterface $dataProvider, IriDataLoader $iriDataLoader)
    {
        $this->dataProvider = $dataProvider;
        $this->iriDataLoader = $iriDataLoader;

        $this->configureArguments();
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        if ($this->name) {
            return $this->name;
        }

        $calledClass = get_called_class();
        if (preg_match('/\\\\(\w+)TypeResolver$/', $calledClass, $matches)) {
            $this->name = lcfirst(str_replace(['\\', 'TypeResolver'], '', $matches[0]));

            return $this->name;
        }

        throw new \LogicException(sprintf('The type resolver %s must be named FooBarTypeResolver', $calledClass));
    }

    /**
     * {@inheritdoc}
     */
    public function getEndpoint(): string
    {
        return $this->getName().'s';
    }

    /**
     * Resolve a Type.
     *
     * @param string $endpoint
     *
     * @return \Closure
     */
    public function resolveTypeClosure(string $endpoint, bool $rawResponse = false)
    {
        return function ($source, $args, $context, ResolveInfo $info) use ($endpoint, $rawResponse) {
            $parameters = [];

            foreach ($args as $argName => $argValue) {
                if (array_key_exists($argName, $this->argumentsConfig)) {
                    $parameters[static::argumentNameToQueryParam($argName)] = $argValue;
                }
            }

            if (is_array($context) && isset($context['root'])) {
                return [$context['root']];
            }
            if (is_array($context) && isset($context['extra_parameters'])) {
                $parameters = array_merge($parameters, $context['extra_parameters']);
            }
            //TODO replace the endpoint by a URL path

            return ($rawResponse)
                ? $this->dataProvider->getRawData($endpoint, $parameters)
                : $this->dataProvider->getResourceCollection($endpoint, $parameters);
        };
    }

    /**
     * Resolve a field in a type.
     *
     * @return \Closure
     */
    public static function resolveFieldClosure(): \Closure
    {
        return function ($source, $args, $context, ResolveInfo $info) {
            if (JsonLdObjectType::$objectTypeFieldName === $info->fieldName && isset($source['@type'])) {
                return $source['@type'];
            }

            if (JsonLdObjectType::$objectIdFieldName === $info->fieldName && isset($source['@id'])) {
                return $source['@id'];
            }

            if (array_key_exists($info->fieldName, $source)) {
                return $source[$info->fieldName];
            }
        };
    }

    /**
     * @param array $path
     *
     * @return int
     */
    protected function getDepthFromPath(array $path)
    {
        $fieldNameDepth = -1;

        foreach ($path as $pathElement) {
            if (is_string($pathElement)) {
                ++$fieldNameDepth;
            }
        }

        return $fieldNameDepth;
    }

    /**
     * Resolve a field representing an IRI or IRI[] or null.
     *
     * @return \Closure
     */
    protected function resolveIriDeferredClosure()
    {
        return function ($source, $args, $context, ResolveInfo $info) {
            $fieldNameDepth = $this->getDepthFromPath($info->path);

            // resolve null value
            if (!array_key_exists($info->fieldName, $source)) {
                return;
            }

            $graphqlContext = [
                'path' => implode('.', $info->path),
                'depth' => $fieldNameDepth,
            ];

            return new Deferred(function () use ($source, $info, $graphqlContext) {
                // resolve only one IRI
                if (is_string($source[$info->fieldName])) {
                    $iri = $source[$info->fieldName];

                    return $this->dataProvider->getIri($iri, $graphqlContext);
                }

                // resolve array of IRIs
                if (is_array($source[$info->fieldName])) {
                    return $this->dataProvider->getIris($source[$info->fieldName], $graphqlContext);
                }
            });
        };
    }

    /**
     * Transforms an argument name to query parameter. Examples:.
     *
     * body                         ===> ?body=
     * vocabulary_dot_vid           ===> ?vocabulary.vid=
     * id_br_op__br_cl_             ===> ?id[]=
     * started_br_op_before_br_cl_  ===> ?started[before]=
     *
     *
     * @param string $argumentName
     *
     * @return string
     */
    protected function argumentNameToQueryParam($argumentName)
    {
        return str_replace([
            static::GEN_DOT,
            static::GEN_BRACKET_OPEN,
            static::GEN_BRACKET_CLOSE,
        ], [
            static::DOT,
            static::BRACKET_OPEN,
            static::BRACKET_CLOSE,
        ], $argumentName);
    }

    /**
     * @param string $queryParam
     *
     * @return string
     */
    public static function queryParamToArgumentName(string $queryParam): string
    {
        return str_replace([
            static::DOT,
            static::BRACKET_OPEN,
            static::BRACKET_CLOSE,
        ], [
            static::GEN_DOT,
            static::GEN_BRACKET_OPEN,
            static::GEN_BRACKET_CLOSE,
        ], $queryParam);
    }

    /**
     * @return array
     */
    public function getArgumentsConfig()
    {
        return $this->argumentsConfig;
    }

    /**
     * @return array
     */
    protected function getDefaultArguments()
    {
        return [
            'id' => ['type' => Type::string()],
            'page' => ['type' => Type::string()],
            'limit' => ['type' => Type::string()],
        ];
    }

    /**
     * @param array $add
     * @param array $remove
     *
     * @return TypeResolver
     */
    protected function configureArguments(array $add = [], array $remove = [])
    {
        // remove from default arguments
        $defaultArguments = static::getDefaultArguments();
        foreach ($remove as $argName) {
            unset($defaultArguments[$argName]);
        }

        // add new arguments
        $this->argumentsConfig = array_merge($defaultArguments, $this->buildArgumentsConfigFrom($add));

        return $this;
    }

    /**
     * @param array $args
     *
     * @return array
     */
    private function buildArgumentsConfigFrom(array $args)
    {
        $argsConfig = [];

        foreach ($args as $key => $value) {
            if (is_int($key)) {
                $argumentDefinition = [
                    'name' => static::queryParamToArgumentName((string) $value),
                    'type' => Type::string(),
                ];
            } else {
                if (is_array($value)) {
                    $argumentDefinition = [
                        'name' => static::queryParamToArgumentName($value['name'] ?? $key),
                        'type' => $value['type'] ?? Type::string(),
                    ];

                    if (isset($value['description'])) {
                        $argumentDefinition['description'] = $value['description'];
                    }

                    if (isset($value['defaultValue'])) {
                        $argumentDefinition['defaultValue'] = $value['defaultValue'];
                    }
                } else {
                    $argumentDefinition = [
                        'name' => static::queryParamToArgumentName((string) $key),
                        'type' => $value,
                    ];
                }
            }

            $argsConfig[$argumentDefinition['name']] = $argumentDefinition;
        }

        return $argsConfig;
    }
}
