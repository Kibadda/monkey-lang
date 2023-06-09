<?php

namespace Monkey\Ast\Expression;

use Monkey\Ast\Node;
use Monkey\Token\Token;

class MatchLiteral implements Expression
{
    /**
     * @param Branch[] $branches
     */
    public function __construct(
        public Token $token,
        public Expression $subject,
        public array $branches,
    ) {
    }

    public function tokenLiteral(): string
    {
        return $this->token->literal;
    }

    public function string(): string
    {
        $branches = [];

        foreach ($this->branches as $branch) {
            $branches[] = "{$branch->condition->string()} -> {$branch->consequence->string()},";
        }

        return "{$this->tokenLiteral()} ({$this->subject->string()}) {\n" . implode("\n", $branches) . "\n}";
    }

    public function modify(callable $modifier): Node
    {
        $this->subject->modify($modifier);

        foreach ($this->branches as $branch) {
            $branch->condition->modify($modifier);
            $branch->consequence->modify($modifier);
        }

        return $modifier($this);
    }
}
