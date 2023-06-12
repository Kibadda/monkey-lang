<?php

namespace Monkey\Ast\Expression;

use Exception;
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
        $right = $this->right->modify($modifier);

        if (!$right instanceof Expression) {
            throw new Exception("modified node `right` does not match class: got Statement, want Expression");
        }

        $this->right = $right;

        return $modifier($this);
    }
}
