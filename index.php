<?php

use Monkey\Compiler\Compiler;
use Monkey\Lexer\Lexer;
use Monkey\Parser\Parser;
use Monkey\VM\VM;

require_once __DIR__ . '/vendor/autoload.php';

$lexer = new Lexer('len(1)');
$parser = new Parser($lexer);
$program = $parser->parseProgam();
$compiler = new Compiler();
$compiler->compile($program);
$vm = new VM($compiler);

$vm->run();

$stackElem = $vm->lastPoppedStackElem();

print_r($stackElem);
