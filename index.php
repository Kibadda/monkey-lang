<?php
require_once __DIR__ . '/vendor/autoload.php';

use Monkey\Evaluator\Environment;
use Monkey\Evaluator\Evaluator;
use Monkey\Lexer\Lexer;
use Monkey\Parser\Parser;

$lexer = Lexer::new('{"one": 1, true: 2, 1: 3}');
$parser = Parser::new($lexer);
$program = $parser->parseProgam();
$environment = Environment::new();

$eval = Evaluator::new($environment)->eval($program);
print_r($eval);
