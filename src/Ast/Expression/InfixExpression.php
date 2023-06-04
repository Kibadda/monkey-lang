<?php

namespace Monkey\Ast\Expression;

use Monkey\Token\Token;

class InfixExpression implements Expression
{
    public function __construct(
        public Token $token,
        public Expression $left,
        public string $operator,
        public Expression $right,
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
        return "({$this->left->string()} {$this->operator} {$this->right->string()})";
    }
}
