<?php

namespace Monkey\Repl;

use Exception;
use Monkey\Compiler\Compiler;
use Monkey\Compiler\SymbolTable;
use Monkey\Lexer\Lexer;
use Monkey\Object\Builtins;
use Monkey\Parser\Parser;
use Monkey\VM\VM;

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
        fwrite(STDOUT, "Hello! This is the Monkey programming language!\nFeel free to type in commands\n");

        $constants = [];
        $globals = [];
        $symbolTable = new SymbolTable();

        $builtins = new Builtins();
        foreach (array_keys($builtins->builtins) as $i => $name) {
            $symbolTable->defineBuiltin($i, $name);
        }

        while (true) {
            fwrite(STDOUT, self::PROMPT);
            $line = fgets(STDIN);

            if (empty($line)) {
                break;
            }

            $lexer = new Lexer($line);
            $parser = new Parser($lexer);

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

            // $environment->extend(Evaluator::defineMacros($program));
            // $expanded = Evaluator::expandMacros($program, $environment);

            // $evaluated = Evaluator::new($environment)->eval($expanded);

            // if (!is_null($evaluated)) {
            //     fwrite(STDOUT, "{$evaluated->inspect()}\n");
            // }

            $compiler = new Compiler($constants, $symbolTable);
            try {
                $compiler->compile($program);
            } catch (Exception $e) {
                fwrite(STDOUT, "Woops! Compilation failed:\n {$e->getMessage()}\n");
                continue;
            }

            $constants = $compiler->constants;

            $vm = new VM($compiler, $globals);
            try {
                $vm->run();
            } catch (Exception $e) {
                fwrite(STDOUT, "Woops! Executing bytecode failed:\n {$e->getMessage()}\n");
                continue;
            }

            $globals = $vm->globals;

            fwrite(STDOUT, "{$vm->lastPoppedStackElem()->inspect()}\n");
        }
    }
}
