<?php

namespace Monkey\Ast\Statement;

use Monkey\Ast\Expression\Expression;
use Monkey\Ast\Modify;
use Monkey\Token\Token;

class ReturnStatement implements Statement
{
    use Modify;

    public function __construct(
        public Token $token,
        public Expression $value,
    ) {
    }

    public function statementNode()
    {
    }

    public function tokenLiteral(): string
    {
        return $this->token->literal;
    }

    public function string(): string
    {
        return "{$this->tokenLiteral()} {$this->value->string()};";
    }
}
