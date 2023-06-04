<?php

namespace Monkey\Ast\Statement;

use Monkey\Ast\Expression\Expression;
use Monkey\Ast\Expression\Identifier;
use Monkey\Token\Token;

class LetStatement implements Statement
{
    public function __construct(
        public Token $token,
        public Identifier $name,
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
        return "{$this->tokenLiteral()} {$this->name->string()} = {$this->value?->string()};";
    }
}