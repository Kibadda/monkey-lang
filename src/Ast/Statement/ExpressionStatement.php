<?php

namespace Monkey\Ast\Statement;

use Monkey\Ast\Expression\Expression;
use Monkey\Ast\Modify;
use Monkey\Token\Token;

class ExpressionStatement implements Statement
{
    use Modify;

    public function __construct(
        public ?Token $token = null,
        public ?Expression $value = null,
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
        return $this->value?->string() ?? '';
    }
}
