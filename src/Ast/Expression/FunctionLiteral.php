<?php

namespace Monkey\Ast\Expression;

use Exception;
use Monkey\Ast\Node;
use Monkey\Ast\Statement\BlockStatement;
use Monkey\Token\Token;

class FunctionLiteral implements Expression
{
    /**
     * @param Identifier[] $parameters
     */
    public function __construct(
        public Token $token,
        public array $parameters,
        public BlockStatement $body,
        public string $name = '',
    ) {
    }

    public function tokenLiteral(): string
    {
        return $this->token->literal;
    }

    public function string(): string
    {
        $parameters = [];

        foreach ($this->parameters as $parameter) {
            $parameters[] = $parameter->string();
        }

        return "{$this->tokenLiteral()}<{$this->name}>(" . implode(', ', $parameters) . ") {$this->body->string()}";
    }

    public function modify(callable $modifier): Node
    {
        $parameters = [];

        foreach ($this->parameters as $parameter) {
            $param = $parameter->modify($modifier);

            if (!$param instanceof Identifier) {
                throw new Exception("modified node `parameter` does not match class: got Statement, want Expression");
            }

            $parameters[] = $param;
        }

        $body = $this->body->modify($modifier);

        if (!$body instanceof BlockStatement) {
            throw new Exception("modified node `body` does not match class: got Expression, want Statement");
        }

        $this->parameters = $parameters;
        $this->body = $body;

        return $modifier($this);
    }
}
