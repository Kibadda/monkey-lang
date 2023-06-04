<?php
require_once __DIR__ . '/vendor/autoload.php';

// Monkey\Repl\Repl::start();


use Monkey\Lexer\Lexer;
use Monkey\Parser\Parser;

$lexer = Lexer::new('5 + 5;');
$parser = Parser::new($lexer);
$program = $parser->parseProgam();
