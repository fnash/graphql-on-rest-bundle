<?php

namespace Fnash\GraphqlOnRestBundle\GraphQL\TypeResolver;

use GraphQL\Type\Definition\Type;

/**
 * Every service implementing this interface must be tagged with 'graphql_on_rest.type_resolver'.
 */
interface TypeResolverInterface
{
    /**
     * Unique name of the object type within Schema.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * @return Type
     */
    public function getType(): Type;

    /**
     * http endpoint.
     *
     * @return string
     */
    public function getEndpoint(): string;

    /**
     * @return array
     */
    public function getQueryFieldConfig(): array;
}
