<?php

use Monkey\Compiler\Compiler;
use Monkey\Lexer\Lexer;
use Monkey\Parser\Parser;
use Monkey\VM\VM;

require_once __DIR__ . '/vendor/autoload.php';

$lexer = new Lexer('let countDown = fn(x) { countDown(x - 1) }; countDown(1)');
$parser = new Parser($lexer);
$program = $parser->parseProgam();
$compiler = new Compiler();
$compiler->compile($program);

print_r($compiler);
