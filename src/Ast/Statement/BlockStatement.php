<?php

namespace Monkey\Ast\Statement;

use Monkey\Ast\Modify;
use Monkey\Token\Token;

class BlockStatement implements Statement
{
    use Modify;

    /**
     * @param Statement[] $statements
     */
    public function __construct(
        public Token $token,
        public array $statements,
    ) {
    }

    public function statementNode()
    {
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
}
