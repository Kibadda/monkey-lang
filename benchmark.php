<?php

if ($argc == 1) {
    exit();
}

use Monkey\Compiler\Compiler;
use Monkey\Evaluator\Evaluator;
use Monkey\Lexer\Lexer;
use Monkey\Object\Environment;
use Monkey\Parser\Parser;
use Monkey\VM\VM;

require_once __DIR__ . '/vendor/autoload.php';

$input = '
let fibonacci = fn(x) {
    if (x == 0) {
        0
    } else {
        if (x == 1) {
            1
        } else {
            fibonacci(x - 1) + fibonacci(x - 2)
        }
    }
};
fibonacci(25)
';

$lexer = new Lexer($input);
$parser = new Parser($lexer);
$program = $parser->parseProgam();

if ($argv[1] == 'vm') {
    $compiler = new Compiler();
    $compiler->compile($program);

    $machine = new VM($compiler);

    $start = microtime(true);

    $machine->run();

    $end = microtime(true);
    $result = $machine->lastPoppedStackElem();
} else if ($argv[1] == 'eval') {
    $environemt = new Environment();
    $evaluator = new Evaluator($environemt);

    $start = microtime(true);

    $result = $evaluator->eval($program);

    $end = microtime(true);
} else {
    exit();
}

$duration = round($end - $start, 4);

fwrite(STDOUT, "engine: {$argv[1]}, result: {$result->inspect()}, duration: {$duration}\n");
