<?php

namespace Monkey\Ast\Expression;

use Monkey\Ast\Node;
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

    public function tokenLiteral(): string
    {
        return $this->token->literal;
    }

    public function string(): string
    {
        return "({$this->left->string()} {$this->operator} {$this->right->string()})";
    }

    public function modify(callable $modifier): Node
    {
        $this->left = $this->left->modify($modifier);
        $this->right = $this->right->modify($modifier);

        return $modifier($this);
    }
}
