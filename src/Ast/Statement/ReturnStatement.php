<?php

namespace Monkey\Ast\Statement;

use Exception;
use Monkey\Ast\Expression\Expression;
use Monkey\Ast\Node;
use Monkey\Token\Token;

class ReturnStatement implements Statement
{
    public function __construct(
        public Token $token,
        public Expression $value,
    ) {
    }

    public function tokenLiteral(): string
    {
        return $this->token->literal;
    }

    public function string(): string
    {
        return "{$this->tokenLiteral()} {$this->value->string()};";
    }

    public function modify(callable $modifier): Node
    {
        $value = $this->value->modify($modifier);

        if (!$value instanceof Expression) {
            throw new Exception("modified node `value` does not match class: got Statement, want Expression");
        }

        $this->value = $value;

        return $modifier($this);
    }
}
