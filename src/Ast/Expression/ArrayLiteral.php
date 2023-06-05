<?php

namespace Monkey\Ast\Expression;

use Monkey\Token\Token;

class ArrayLiteral implements Expression
{
    /**
     * @param Expression[] $elements
     */
    public function __construct(
        public Token $token,
        public array $elements,
    ) {
    }

    public function expressionNode()
    {
    }

    public function tokenLiteral(): string
    {
        return $this->token->literal;
    }

    public function string(): string
    {
        $elements = [];

        foreach ($this->elements as $element) {
            $elements[] = $element->string();
        }

        return '[' . implode(', ', $elements) . ']';
    }
}
