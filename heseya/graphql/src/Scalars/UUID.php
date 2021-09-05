<?php

namespace Heseya\GraphQL\Scalars;

use GraphQL\Error\Error;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;

class UUID extends ScalarType
{
    public function serialize($value): string
    {
        return (string) $value;
    }

    public function parseValue($value): string
    {
        return (string) $value;
    }

    public function parseLiteral(Node $valueNode, ?array $variables = null): string
    {
        if (! $valueNode instanceof StringValueNode) {
            throw new Error(
                "Query error: Can only parse strings, got {$valueNode->kind}",
                $valueNode,
            );
        }

        return (string) $valueNode->value;
    }
}
