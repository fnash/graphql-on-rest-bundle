<?php

namespace Fnash\GraphqlOnRestBundle\GraphQL\Type;

use GraphQL\Language\AST\Node as AstNode;
use GraphQL\Language\AST\StringValue;
use GraphQL\Type\Definition\ScalarType;

class DateTimeType extends ScalarType
{
    /**
     * @var string
     */
    public $name = 'DateTimeScalar';
    /**
     * @var string
     */
    public $description = 'A Date and time, represented as ISO 8601 conform string';

    /**
     * @param \DateTimeInterface $value
     *
     * @return string
     */
    public function serialize($value)
    {
        if (!$value instanceof \DateTimeInterface) {
            return null;
        }

        return $value->format(\DateTime::ISO8601);
    }

    /**
     * @param string $value
     *
     * @return \DateTimeImmutable
     */
    public function parseValue($value)
    {
        if (!is_string($value)) {
            return null;
        }

        $dateTime = \DateTimeImmutable::createFromFormat(\DateTime::ISO8601, $value);

        if (false === $dateTime) {
            return null;
        }

        return $dateTime;
    }

    /**
     * @param AstNode $valueAST
     *
     * @return \DateTimeImmutable
     */
    public function parseLiteral($valueAST)
    {
        if (!$valueAST instanceof StringValue) {
            return null;
        }

        return $this->parseValue($valueAST->value);
    }
}
