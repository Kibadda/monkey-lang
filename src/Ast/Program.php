<?php

namespace Monkey\Ast;

use Exception;
use Monkey\Ast\Statement\Statement;

class Program implements Node
{
    /** @var Statement[] $statements */
    public array $statements;

    /**
     * @param Statement[] $statements
     */
    public function __construct(array $statements = [])
    {
        $this->statements = $statements;
    }

    public function tokenLiteral(): string
    {
        if (count($this->statements) > 0) {
            return $this->statements[0]->tokenLiteral();
        } else {
            return '';
        }
    }

    public function string(): string
    {
        $string = '';

        foreach ($this->statements as $statement) {
            $string .= $statement->string();
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
