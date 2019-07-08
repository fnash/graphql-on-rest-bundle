<?php

namespace Fnash\GraphqlOnRestBundle\GraphQL\TypeResolver;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

class ListTypeResolverFactory
{
    /**
     * @var string
     */
    private $resourceCollectionKey;

    /**
     * @var string
     */
    private $totalItemsKey;

    public function __construct(string $resourceCollectionKey, string $totalItemsKey)
    {
        $this->resourceCollectionKey = $resourceCollectionKey;
        $this->totalItemsKey = $totalItemsKey;
    }

    /**
     * @param TypeResolverInterface $typeResolver
     *
     * @return string
     */
    public function getListTypeName(TypeResolverInterface $typeResolver)
    {
        return $typeResolver->getName().'List';
    }

    /**
     * @param TypeResolverInterface $typeResolver
     *
     * @return ObjectType
     */
    public function createListTypeResolver(TypeResolverInterface $typeResolver)
    {
        return new ObjectType([
            'name' => $this->getListTypeName($typeResolver),
            'fields' => function () use ($typeResolver) {
                return [
                    '_TotalCount_' => [
                        'type' => Type::string(),
                        'resolve' => function ($source, $args, $context, ResolveInfo $info) {
                            return $source[$this->totalItemsKey] ?? null;
                        },
                    ],
                    'nodes' => [
                        'type' => Type::listOf($typeResolver->getType()),
                        'resolve' => function ($source, $args, $context, ResolveInfo $info) {
                            return $source[$this->resourceCollectionKey] ?? [];
                        },
                    ],
                ];
            },
        ]);
    }

    /**
     * @param TypeResolver $typeResolver
     *
     * @return array
     */
    public function getQueryFieldConfig(TypeResolver $typeResolver)
    {
        return [
            'type' => $this->createListTypeResolver($typeResolver),
            'resolve' => $typeResolver->resolveTypeClosure($typeResolver->getUrlPath(), true),
            'args' => $typeResolver->getArgumentsConfig(),
        ];
    }
}
