<?php

namespace Monkey\Ast\Expression;

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

        return "{$this->tokenLiteral()}(" . implode(', ', $parameters) . ") {$this->body->string()}";
    }

    public function modify(callable $modifier): Node
    {
        $this->parameters = array_map(fn (Identifier $parameter) => $parameter->modify($modifier), $this->parameters);
        $this->body = $this->body->modify($modifier);

        return $modifier($this);
    }
}
