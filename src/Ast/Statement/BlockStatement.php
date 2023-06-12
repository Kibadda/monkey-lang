<?php

namespace Monkey\Ast\Statement;

use Exception;
use Monkey\Ast\Node;
use Monkey\Token\Token;

class BlockStatement implements Statement
{
    /**
     * @param Statement[] $statements
     */
    public function __construct(
        public Token $token,
        public array $statements,
    ) {
    }

    public function tokenLiteral(): string
    {
        return $this->token->literal;
    }

    public function string(): string
    {
        $string = '';

        foreach ($this->statements as $stmt) {
            $string .= $stmt->string();
        }

        return $string;
    }

    public function modify(callable $modifier): Node
    {
        $statements = [];
        foreach ($this->statements as $statement) {
            $stmt = $statement->modify($modifier);

            if (!$stmt instanceof Statement) {
                throw new Exception("modified node `statement` does not match class: got Expression, want Statement");
            }

            $statements[] = $stmt;
        }

        $this->statements = $statements;

        return $modifier($this);
    }
}
