<?php

namespace Monkey\Ast\Statement;

use Monkey\Ast\Expression\Expression;
use Monkey\Token\Token;

class ReturnStatement implements Statement
{
    public function __construct(
        public Token $token,
        public ?Expression $value = null,
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
        return "{$this->tokenLiteral()} {$this->value?->string()};";
    }
}
