<?php

namespace Monkey\Ast;

class Program
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
}
