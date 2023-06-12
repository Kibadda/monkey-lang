<?php

namespace Monkey\Ast\Expression;

use Exception;
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
        $elements = [];

        foreach ($this->elements as $element) {
            $elem = $element->modify($modifier);

            if (!$elem instanceof Expression) {
                throw new Exception("modified node `element` does not match class: got Statement, want Expression");
            }

            $elements[] = $elem;
        }

        $this->elements = $elements;

        return $modifier($this);
    }
}
