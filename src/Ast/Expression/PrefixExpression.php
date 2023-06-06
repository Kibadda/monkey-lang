<?php

namespace Monkey\Ast\Expression;

use Monkey\Ast\Node;
use Monkey\Token\Token;

class PrefixExpression implements Expression
{
    public function __construct(
        public Token $token,
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
        return "({$this->operator}{$this->right->string()})";
    }

    public function modify(callable $modifier): Node
    {
        $this->right = $this->right->modify($modifier);

        return $modifier($this);
    }
}
