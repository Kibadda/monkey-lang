<?php

use Monkey\Code\Code;
use Monkey\Compiler\Compiler;
use Monkey\Compiler\Instructions;
use Monkey\Evaluator\Object\EvalInteger;

it('compiles', function (string $input, array $expectedConstants, array $expectedInstructions) {
    $program = createProgram($input);

    $compiler = new Compiler();

    expect($compiler->compile($program))->not->toThrow(Exception::class);
    foreach ($compiler->constants as $i => $constant) {
        expect($constant)->toBeInstanceOf($expectedConstants[$i][0]);
        expect($constant->value)->toBe($expectedConstants[$i][1]);
    }
    $concatted = Instructions::merge(...$expectedInstructions);
    expect($compiler->instructions->count())->toBe($concatted->count());
    foreach ($compiler->instructions as $i => $instruction) {
        expect($instruction)->toBe($concatted[$i]);
    }
})->with([
    ['1 + 2', [[EvalInteger::class, 1], [EvalInteger::class, 2]], [Code::make(Code::CONSTANT, 0), Code::make(Code::CONSTANT, 1)]],
]);

it('stringyfies', function () {
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
    expect($concatted->string())->toBe($expected, json_encode($concatted));
});

it('reads', function (Code $code, $operands, $bytesRead) {
    $instruction = Code::make($code, ...$operands);

    $definition = $code->definition();

    list($operandsRead, $n) = Code::readOperands($definition, $instruction->slice(1));
    expect($n)->toBe($bytesRead);
    foreach ($operands as $i => $operand) {
        expect($operandsRead[$i])->toBe($operand);
    }
})->with([
    [Code::CONSTANT, [65535], 2],
]);
