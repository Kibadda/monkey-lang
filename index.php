<?php

use Monkey\Compiler\Compiler;
use Monkey\Lexer\Lexer;
use Monkey\Parser\Parser;
use Monkey\VM\VM;

require_once __DIR__ . '/vendor/autoload.php';

$lexer = Lexer::new('let one = fn() { 1 }; let two = fn() { 2 }; ');
$parser = Parser::new($lexer);
$program = $parser->parseProgam();

$compiler = new Compiler();

$compiler->compile($program);

print_r($compiler->currentInstructions()->string());

$vm = VM::new($compiler);
$vm->run();

print_r($vm->lastPoppedStackElem());
