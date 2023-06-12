<?php

namespace Monkey\Ast\Expression;

use Exception;
use Monkey\Ast\Node;
use Monkey\Token\Token;

class HashLiteral implements Expression
{
    /**
     * @param Pair[] $pairs
     */
    public function __construct(
        public Token $token,
        public array $pairs,
    ) {
    }

    public function tokenLiteral(): string
    {
        return $this->token->literal;
    }

    public function string(): string
    {
        $pairs = [];

        foreach ($this->pairs as $pair) {
            $pairs[] = "{$pair->key->string()}:{$pair->value->string()}";
        }

        return '{' . implode(', ', $pairs) . '}';
    }

    public function modify(callable $modifier): Node
    {
        foreach ($this->pairs as $pair) {
            $key = $pair->key->modify($modifier);
            $value = $pair->value->modify($modifier);

            if (!$key instanceof Expression) {
                throw new Exception("modified node `pair.key` does not match class: got Statement, want Expression");
            }
            if (!$value instanceof Expression) {
                throw new Exception("modified node `pair.value` does not match class: got Statement, want Expression");
            }

            $pair->key = $key;
            $pair->value = $value;
        }

        return $modifier($this);
    }
}
