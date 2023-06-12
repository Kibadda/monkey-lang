<?php

namespace Monkey\Ast\Expression;

use Exception;
use Monkey\Ast\Node;
use Monkey\Ast\Statement\BlockStatement;
use Monkey\Token\Token;

class IfExpression implements Expression
{
    public function __construct(
        public Token $token,
        public Expression $condition,
        public BlockStatement $consequence,
        public ?BlockStatement $alternative = null,
    ) {
    }

    public function tokenLiteral(): string
    {
        return $this->token->literal;
    }

    public function string(): string
    {
        return "if {$this->condition->string()} {$this->consequence->string()}" . ($this->alternative ? " else {$this->alternative->string()}" : '');
    }

    public function modify(callable $modifier): Node
    {
        $condition = $this->condition->modify($modifier);
        $consequence = $this->consequence->modify($modifier);

        if (!$condition instanceof Expression) {
            throw new Exception("modified node `condition` does not match class: got Statement, want Expression");
        }
        if (!$consequence instanceof BlockStatement) {
            throw new Exception("modified node `consequence` does not match class: got Expression, want Statement");
        }

        $this->condition = $condition;
        $this->consequence = $consequence;

        if ($this->alternative != null) {
            $alternative = $this->alternative->modify($modifier);

            if (!$alternative instanceof BlockStatement) {
                throw new Exception("modified node `alternative` does not match class: got Expression, want BlockStatement");
            }

            $this->alternative = $alternative;
        }

        return $modifier($this);
    }
}
