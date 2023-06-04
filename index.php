<?php
require_once __DIR__ . '/vendor/autoload.php';

use Monkey\Lexer\Lexer;
use Monkey\Parser\Parser;

$lexer = Lexer::new('a + add(b * c) + d');
$parser = Parser::new($lexer);
$program = $parser->parseProgam();

print_r($program->string());
