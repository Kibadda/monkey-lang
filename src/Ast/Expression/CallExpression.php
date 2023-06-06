<?php

namespace Monkey\Ast\Expression;

use Monkey\Ast\Node;
use Monkey\Token\Token;

class CallExpression implements Expression
{
    /**
     * @param Expression[] $arguments
     */
    public function __construct(
        public Token $token,
        public Expression $function,
        public array $arguments,
    ) {
    }

    public function tokenLiteral(): string
    {
        return $this->token->literal;
    }

    public function string(): string
    {
        $arguments = [];

        foreach ($this->arguments as $parameter) {
            $arguments[] = $parameter->string();
        }

        return "{$this->function->string()}(" . implode(', ', $arguments) . ")";
    }

    public function modify(callable $modifier): Node
    {
        return $modifier($this);
    }
}
