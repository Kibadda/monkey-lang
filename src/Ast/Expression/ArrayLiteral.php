<?php

namespace Monkey\Ast\Expression;

use Monkey\Ast\Node;
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

    public function modify(callable $modifier): Node
    {
        $this->elements = array_map(fn (Expression $element) => $element->modify($modifier), $this->elements);

        return $modifier($this);
    }
}
