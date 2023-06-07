<?php

require_once __DIR__ . '/vendor/autoload.php';

use Monkey\Code\Code;
use Monkey\Compiler\Instructions;

$instructions = [
    Code::make(Code::CONSTANT, 1),
    Code::make(Code::CONSTANT, 2),
    Code::make(Code::CONSTANT, 65535),
];

$expected = '0000 CONSTANT 1
0003 CONSTANT 2
0006 CONSTANT 65535
';

$concatted = Instructions::merge(...$instructions);

print_r($concatted->string());
