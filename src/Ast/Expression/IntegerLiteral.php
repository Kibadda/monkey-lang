<?php

namespace Monkey\Ast\Expression;

use Monkey\Ast\Modify;
use Monkey\Token\Token;

class IntegerLiteral implements Expression
{
    use Modify;

    public function __construct(
        public ?Token $token,
        public int $value,
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
