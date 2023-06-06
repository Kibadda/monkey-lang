<?php

namespace Monkey\Ast\Expression;

use Monkey\Ast\Node;
use Monkey\Token\Token;

class HashLiteral implements Expression
{
    /**
     * @param array<Expression[]> $pairs
     */
    public function __construct(
        public Token $token,
        public array $pairs,
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
        $pairs = [];

        foreach ($this->pairs as $element) {
            $pairs[] = "{$element[0]->string()}:{$element[1]->string()}";
        }

        return '{' . implode(', ', $pairs) . '}';
    }

    public function modify(callable $modifier): Node
    {
        $this->pairs = array_map(fn (array $pair) => [$pair[0]->modify($modifier), $pair[1]->modify($modifier)], $this->pairs);

        return $modifier($this);
    }
}
