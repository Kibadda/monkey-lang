<?php

namespace Monkey\Ast\Statement;

use Monkey\Ast\Expression\Expression;
use Monkey\Ast\Node;
use Monkey\Token\Token;

class ExpressionStatement implements Statement
{
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
        return $this->value->string();
    }

    public function modify(callable $modifier): Node
    {
        $this->value = $this->value->modify($modifier);

        return $modifier($this);
    }
}
