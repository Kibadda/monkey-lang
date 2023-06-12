<?php

namespace Monkey\Ast\Expression;

use Exception;
use Monkey\Ast\Node;
use Monkey\Token\Token;

class IndexExpression implements Expression
{
    public function __construct(
        public Token $token,
        public Expression $left,
        public Expression $index,
    ) {
    }

    public function tokenLiteral(): string
    {
        return $this->token->literal;
    }

    public function string(): string
    {
        return "({$this->left->string()}[{$this->index->string()}])";
    }

    public function modify(callable $modifier): Node
    {
        $left = $this->left->modify($modifier);
        $index = $this->index->modify($modifier);

        if (!$left instanceof Expression) {
            throw new Exception("modified node `condition` does not match class: got Statement, want Expression");
        }
        if (!$index instanceof Expression) {
            throw new Exception("modified node `condition` does not match class: got Statement, want Expression");
        }

        $this->left = $left;
        $this->index = $index;

        return $modifier($this);
    }
}
