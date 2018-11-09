<?php

namespace Fnash\GraphqlOnRestBundle\GraphQL\Type;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\UnionType;

/**
 * Class TypeRegistry.
 */
class TypeRegistry
{
    /**
     * @var array
     */
    private static $types = [];

    /**
     * @var array
     */
    private static $interfaces = [];

    /**
     * @var array
     */
    private static $unions = [];

    /**
     * @param $fqcnType
     * @param array $arguments
     *
     * @return ObjectType
     */
    public static function get($fqcnType, array $arguments = []): ObjectType
    {
        if (!array_key_exists($fqcnType, static::$types)) {

            if (!class_exists($fqcnType)) {
                throw new \BadMethodCallException(sprintf('%s is not a defined ObjectType', $fqcnType));
            }

            static::$types[$fqcnType] = new $fqcnType(...$arguments);
        }

        return static::$types[$fqcnType];
    }

    /**
     * @param $fqcnType
     * @param array $arguments
     *
     * @return InterfaceType
     */
    public static function getInterface($fqcnType, array $arguments = []): InterfaceType
    {
        if (!array_key_exists($fqcnType, static::$interfaces)) {

            if (!class_exists($fqcnType)) {
                throw new \BadMethodCallException(sprintf('%s is not a defined InterfaceType', $fqcnType));
            }

            static::$interfaces[$fqcnType] = new $fqcnType(...$arguments);
        }

        return static::$interfaces[$fqcnType];
    }

    /**
     * @param $fqcnType
     * @param array $arguments
     *
     * @return UnionType
     */
    public static function getUnion($fqcnType, array $arguments = []): UnionType
    {
        if (!array_key_exists($fqcnType, static::$unions)) {

            if (!class_exists($fqcnType)) {
                throw new \BadMethodCallException(sprintf('%s is not a defined UnionType', $fqcnType));
            }

            static::$unions[$fqcnType] = new $fqcnType(...$arguments);
        }

        return static::$unions[$fqcnType];
    }
}
