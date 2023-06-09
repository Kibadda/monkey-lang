<?php

use Monkey\Lexer\Lexer;
use Monkey\Parser\Parser;

require_once __DIR__ . '/vendor/autoload.php';

$lexer = new Lexer('match (a) { 1 -> true, "one" -> 2 + 2, };');
$parser = new Parser($lexer);

$program = $parser->parseProgam();
