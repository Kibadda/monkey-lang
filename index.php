<?php
require_once __DIR__ . '/vendor/autoload.php';

use Lexer\Lexer;

$lexer = Lexer::new('let five = 5;
let ten = 10;

let add = fn(x, y) {
  x + y;
};

let result = add(five, ten);
');

$tokens = [];
while (!empty($lexer->ch)) {
    $token = $lexer->nextToken();
    echo "{$token->type->name} {$token->literal}" . PHP_EOL;
}

// print_r($tokens);
