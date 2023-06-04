<?php
require_once __DIR__ . '/vendor/autoload.php';

use Monkey\Evaluator\Evaluator;
use Monkey\Lexer\Lexer;
use Monkey\Parser\Parser;

$lexer = Lexer::new('5;');
$parser = Parser::new($lexer);
$program = $parser->parseProgam();

print_r(Evaluator::eval($program));
