<?php

namespace Monkey\Repl;

use Monkey\Evaluator\Environment;
use Monkey\Evaluator\Evaluator;
use Monkey\Lexer\Lexer;
use Monkey\Parser\Parser;

class Repl
{
    private const PROMPT = '>> ';
    private const MONKEY_FACE = '                 __,__
        .--.  .-"     "-.  .--.
       / .. \/  .-. .-.  \/ .. \
      | |  `|  /   Y   \  |´  | |
      | \   \  \ 0 | 0 /  /   / |
       \ `- ,\.-"""""""-./, -´ /
        ``-´ /_   ^ ^   _\ `-´´
            |  \._   _./  |
            \   \ `~´ /   /
             `._ `-=-´ _.´
                `-----´';

    public static function start()
    {
        $environment = Environment::new();

        fwrite(STDOUT, "Hello! This ist the Monkey programming language!\nFeel free to type in commands\n");

        while (true) {
            fwrite(STDOUT, self::PROMPT);
            $line = fgets(STDIN);

            if (empty($line)) {
                break;
            }

            $lexer = Lexer::new($line);
            $parser = Parser::new($lexer);

            $program = $parser->parseProgam();

            if (count($parser->errors) > 0) {
                fwrite(STDOUT, self::MONKEY_FACE);
                fwrite(STDOUT, "\nWoops! We ran into some monkey business here!\n");
                fwrite(STDOUT, " parser errors:\n");
                foreach ($parser->errors as $error) {
                    fwrite(STDOUT, "\t{$error}\n");
                }
                continue;
            }

            $evaluated = Evaluator::new($environment)->eval($program);

            if (!is_null($evaluated)) {
                fwrite(STDOUT, "{$evaluated->inspect()}\n");
            }
        }
    }
}
