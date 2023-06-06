<?php

namespace Monkey\Ast\Expression;

use Monkey\Ast\Node;
use Monkey\Token\Token;

class Identifier implements Expression
{
    public function __construct(
        public Token $token,
        public string $value,
    ) {
    }

    public function tokenLiteral(): string
    {
        return $this->token->literal;
    }

    public function string(): string
    {
        return $this->value;
    }

    public function modify(callable $modifier): Node
    {
        return $modifier($this);
    }
}
