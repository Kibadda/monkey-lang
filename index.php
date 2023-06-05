<?php
require_once __DIR__ . '/vendor/autoload.php';

use Monkey\Evaluator\Environment;
use Monkey\Evaluator\Evaluator;
use Monkey\Lexer\Lexer;
use Monkey\Parser\Parser;

$lexer = Lexer::new('
    let number = 1;
    let function  = fn(x, y) { x + y; };
    let mymacro = macro(x, y) { x + y; };
');
$parser = Parser::new($lexer);
$program = $parser->parseProgam();

print_r($program);

$env = Evaluator::defineMacros($program);

print_r($env);
