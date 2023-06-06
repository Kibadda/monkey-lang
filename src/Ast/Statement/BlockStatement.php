<?php

namespace Monkey\Ast\Statement;

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
        $this->statements = array_map(fn (Statement $statement) => $statement->modify($modifier), $this->statements);

        return $modifier($this);
    }
}
