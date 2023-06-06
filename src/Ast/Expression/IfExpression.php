<?php

namespace Monkey\Ast\Expression;

use Monkey\Ast\Modify;
use Monkey\Ast\Statement\BlockStatement;
use Monkey\Token\Token;

class IfExpression implements Expression
{
    use Modify;

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
}
