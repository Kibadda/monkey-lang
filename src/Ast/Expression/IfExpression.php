<?php

namespace Monkey\Ast\Expression;

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

    public function expressionNode()
    {
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
        $this->condition = $this->condition->modify($modifier);
        $this->consequence = $this->consequence->modify($modifier);
        $this->alternative = is_null($this->alternative) ?: $this->alternative->modify($modifier);

        return $modifier($this);
    }
}
