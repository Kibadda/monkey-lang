<?php

namespace Monkey\Ast\Expression;

use Monkey\Token\Token;

class Boolean implements Expression
{
    public function __construct(
        public Token $token,
        public bool $value,
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
        return $this->token->literal;
    }
}
