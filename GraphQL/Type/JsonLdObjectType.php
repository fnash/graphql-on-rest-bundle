<?php

namespace Fnash\GraphqlOnRestBundle\GraphQL\Type;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

abstract class JsonLdObjectType extends ObjectType
{
    public static $objectTypeFieldName = '_ObjectType_';
    public static $objectIdFieldName = '_ObjectId_';

    /**
     * @return array
     */
    public static function getMetaDataFields()
    {
        return [
            static::$objectTypeFieldName => Type::nonNull(Type::string()),
            static::$objectIdFieldName => Type::nonNull(Type::string()),
        ];
    }
}
