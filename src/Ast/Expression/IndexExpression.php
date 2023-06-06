<?php

namespace Monkey\Ast\Expression;

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

    public function expressionNode()
    {
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
        $this->left = $this->left->modify($modifier);
        $this->index = $this->index->modify($modifier);

        return $modifier($this);
    }
}
