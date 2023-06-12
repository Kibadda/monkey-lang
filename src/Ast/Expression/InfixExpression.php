<?php

namespace Monkey\Ast\Expression;

use Exception;
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
        $left = $this->left->modify($modifier);
        $right = $this->right->modify($modifier);

        if (!$left instanceof Expression) {
            throw new Exception("modified node `left` does not match class: got Statement, want Expression");
        }
        if (!$right instanceof Expression) {
            throw new Exception("modified node `right` does not match class: got Statement, want Expression");
        }

        $this->left = $left;
        $this->right = $right;

        return $modifier($this);
    }
}
