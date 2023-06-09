<?php

use Monkey\Code\Code;
use Monkey\Compiler\Instructions;

require_once __DIR__ . '/vendor/autoload.php';

$instructions = [Code::CONSTANT->make(0), Code::CONSTANT->make(1), Code::ADD->make(), Code::POP->make()];

print_r(Instructions::from($instructions));
print_r(new Instructions($instructions));
