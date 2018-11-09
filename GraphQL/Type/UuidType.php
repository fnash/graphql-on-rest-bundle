<?php

namespace Fnash\GraphqlOnRestBundle\GraphQL\Type;

use GraphQL\Language\AST\Node as AstNode;
use GraphQL\Language\AST\StringValue;
use GraphQL\Type\Definition\ScalarType;

class UuidType extends ScalarType
{
    const PATTERN_MATCH_UUID = '/^([a-f0-9]){8}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){4}-([a-f0-9]){12}$/';

    /**
     * @var string
     */
    public $name = 'UUID';

    /**
     * @var string
     */
    public $description = 'A UUID represented as string';

    /**
     * @param string $value
     *
     * @return string
     */
    public function serialize($value)
    {
        return self::isValid($value) ? $value : null;
    }

    /**
     * @param string $value
     *
     * @return string
     */
    public function parseValue($value)
    {
        return self::isValid($value) ? $value : null;
    }

    /**
     * @param AstNode $valueAST
     *
     * @return string
     */
    public function parseLiteral($valueAST)
    {
        if (!$valueAST instanceof StringValue) {
            return null;
        }

        return $this->parseValue($valueAST->value);
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    public static function isValid($value)
    {
        return is_string($value) && 1 === preg_match(self::PATTERN_MATCH_UUID, $value);
    }
}
