<?php

namespace Monkey\Repl;

use Monkey\Lexer\Lexer;
use Monkey\Lib\Type;

class Repl
{
    private const PROMPT = '>> ';

    public static function start()
    {
        fwrite(STDOUT, "Hello! This ist the Monkey programming language!\nFeel free to type in commands\n");
        while (true) {
            fwrite(STDOUT, self::PROMPT);
            $line = fgets(STDIN);

            if (empty($line)) {
                break;
            }

            $lexer = Lexer::new($line);

            while (($token = $lexer->nextToken())->type != Type::EOF) {
                fwrite(STDOUT, "{$token->type->name}: {$token->literal}\n");
            }
        }
    }
}
