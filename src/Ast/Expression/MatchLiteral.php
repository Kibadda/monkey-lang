<?php

namespace Monkey\Ast\Expression;

use Exception;
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
        public ?Expression $default,
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

        return "{$this->tokenLiteral()} ({$this->subject->string()}) {\n" . implode("\n", $branches) . ($this->default ? "\n? -> {$this->default->string()}" : '') . "\n}";
    }

    public function modify(callable $modifier): Node
    {
        $subject = $this->subject->modify($modifier);

        if (!$subject instanceof Expression) {
            throw new Exception("modified node `subject` does not match class: got Statement, want Expression");
        }

        $this->subject = $subject;

        foreach ($this->branches as $branch) {
            $condition = $branch->condition->modify($modifier);
            $consequence = $branch->consequence->modify($modifier);

            if (!$condition instanceof Expression) {
                throw new Exception("modified node `branch.condition` does not match class: got Statement, want Expression");
            }
            if (!$consequence instanceof Expression) {
                throw new Exception("modified node `branch.consequence` does not match class: got Statement, want Expression");
            }

            $branch->condition = $condition;
            $branch->consequence = $consequence;
        }

        if ($this->default != null) {
            $default = $this->default->modify($modifier);

            if (!$default instanceof Expression) {
                throw new Exception("modified node `default` does not match class: got Statement, want Expression");
            }

            $this->default = $default;
        }

        return $modifier($this);
    }
}
