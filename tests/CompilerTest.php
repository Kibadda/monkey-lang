<?php

use Monkey\Code\Code;
use Monkey\Compiler\Compiler;
use Monkey\Compiler\Instructions;
use Monkey\Evaluator\Object\EvalCompiledFunction;
use Monkey\Evaluator\Object\EvalInteger;
use Monkey\Evaluator\Object\EvalString;

it('compiles', function (string $input, array $expectedConstants, array $expectedInstructions) {
    $program = createProgram($input);

    $compiler = new Compiler();

    expect($compiler->compile($program))->not->toThrow(Exception::class);
    $concatted = Instructions::merge(...$expectedInstructions);
    expect($compiler->currentInstructions()->string())->toBe($concatted->string());
    expect($compiler->currentInstructions()->count())->toBe($concatted->count());
    foreach ($compiler->currentInstructions()->elements as $i => $instruction) {
        expect($instruction)->toBe($concatted[$i]);
    }
    foreach ($compiler->constants as $i => $constant) {
        expect($constant)->toBeInstanceOf($expectedConstants[$i][0]);
        if ($constant instanceof EvalCompiledFunction) {
            $con = Instructions::merge(...$expectedConstants[$i][1]);
            expect($constant->instructions->string())->toBe($con->string());
            expect($constant->instructions->count())->toBe($con->count());
            foreach ($constant->instructions->elements as $j => $instruction) {
                expect($instruction)->toBe($con[$j]);
            }
        } else {
            expect($constant->value)->toBe($expectedConstants[$i][1]);
        }
    }
})->with([
    [
        '1 + 2',
        [[EvalInteger::class, 1], [EvalInteger::class, 2]],
        [Code::CONSTANT->make(0), Code::CONSTANT->make(1), Code::ADD->make(), Code::POP->make()],
    ],
    [
        '1; 2',
        [[EvalInteger::class, 1], [EvalInteger::class, 2]],
        [Code::CONSTANT->make(0), Code::POP->make(), Code::CONSTANT->make(1), Code::POP->make()],
    ],
    [
        '1 - 2',
        [[EvalInteger::class, 1], [EvalInteger::class, 2]],
        [Code::CONSTANT->make(0), Code::CONSTANT->make(1), Code::SUB->make(), Code::POP->make()],
    ],
    [
        '1 * 2',
        [[EvalInteger::class, 1], [EvalInteger::class, 2]],
        [Code::CONSTANT->make(0), Code::CONSTANT->make(1), Code::MUL->make(), Code::POP->make()],
    ],
    [
        '2 / 1',
        [[EvalInteger::class, 2], [EvalInteger::class, 1]],
        [Code::CONSTANT->make(0), Code::CONSTANT->make(1), Code::DIV->make(), Code::POP->make()],
    ],
    [
        'true',
        [],
        [Code::TRUE->make(), Code::POP->make()],
    ],
    [
        'false',
        [],
        [Code::FALSE->make(), Code::POP->make()],
    ],
    [
        '1 > 2',
        [[EvalInteger::class, 1], [EvalInteger::class, 2]],
        [Code::CONSTANT->make(0), Code::CONSTANT->make(1), Code::GREATER_THAN->make(), Code::POP->make()],
    ],
    [
        '1 < 2',
        [[EvalInteger::class, 2], [EvalInteger::class, 1]],
        [Code::CONSTANT->make(0), Code::CONSTANT->make(1), Code::GREATER_THAN->make(), Code::POP->make()],
    ],
    [
        '1 == 2',
        [[EvalInteger::class, 1], [EvalInteger::class, 2]],
        [Code::CONSTANT->make(0), Code::CONSTANT->make(1), Code::EQUAL->make(), Code::POP->make()],
    ],
    [
        '1 != 2',
        [[EvalInteger::class, 1], [EvalInteger::class, 2]],
        [Code::CONSTANT->make(0), Code::CONSTANT->make(1), Code::NOT_EQUAL->make(), Code::POP->make()],
    ],
    [
        'true == false',
        [],
        [Code::TRUE->make(), Code::FALSE->make(), Code::EQUAL->make(), Code::POP->make()],
    ],
    [
        'true != false',
        [],
        [Code::TRUE->make(), Code::FALSE->make(), Code::NOT_EQUAL->make(), Code::POP->make()],
    ],
    [
        '-1',
        [[EvalInteger::class, 1]],
        [Code::CONSTANT->make(0), Code::MINUS->make(), Code::POP->make()],
    ],
    [
        '!true',
        [],
        [Code::TRUE->make(), Code::BANG->make(), Code::POP->make()],
    ],
    [
        'if (true) { 10 }; 3333',
        [[EvalInteger::class, 10], [EvalInteger::class, 3333]],
        [Code::TRUE->make(), Code::JUMP_NOT_TRUTHY->make(10), Code::CONSTANT->make(0), Code::JUMP->make(11), Code::NULL->make(), Code::POP->make(), Code::CONSTANT->make(1), Code::POP->make()],
    ],
    [
        'if (true) { 10 } else { 20 }; 3333',
        [[EvalInteger::class, 10], [EvalInteger::class, 20], [EvalInteger::class, 3333]],
        [Code::TRUE->make(), Code::JUMP_NOT_TRUTHY->make(10), Code::CONSTANT->make(0), Code::JUMP->make(13), Code::CONSTANT->make(1), Code::POP->make(), Code::CONSTANT->make(2), Code::POP->make()],
    ],
    [
        'let one = 1; let two = 2;',
        [[EvalInteger::class, 1], [EvalInteger::class, 2]],
        [Code::CONSTANT->make(0), Code::SET_GLOBAL->make(0), Code::CONSTANT->make(1), Code::SET_GLOBAL->make(1)],
    ],
    [
        'let one = 1; one;',
        [[EvalInteger::class, 1]],
        [Code::CONSTANT->make(0), Code::SET_GLOBAL->make(0), Code::GET_GLOBAL->make(0), Code::POP->make()],
    ],
    [
        'let one = 1; let two = one; two;',
        [[EvalInteger::class, 1]],
        [Code::CONSTANT->make(0), Code::SET_GLOBAL->make(0), Code::GET_GLOBAL->make(0), Code::SET_GLOBAL->make(1), Code::GET_GLOBAL->make(1), Code::POP->make()],
    ],
    [
        '"monkey"',
        [[EvalString::class, 'monkey']],
        [Code::CONSTANT->make(0), Code::POP->make()],
    ],
    [
        '"mon" + "key"',
        [[EvalString::class, 'mon'], [EvalString::class, 'key']],
        [Code::CONSTANT->make(0), Code::CONSTANT->make(1), Code::ADD->make(), Code::POP->make()],
    ],
    [
        '[]',
        [],
        [Code::ARRAY->make(0), Code::POP->make()],
    ],
    [
        '[1, 2, 3]',
        [[EvalInteger::class, 1], [EvalInteger::class, 2], [EvalInteger::class, 3]],
        [Code::CONSTANT->make(0), Code::CONSTANT->make(1), Code::CONSTANT->make(2), Code::ARRAY->make(3), Code::POP->make()],
    ],
    [
        '[1 + 2, 3 - 4, 5 * 6]',
        [[EvalInteger::class, 1], [EvalInteger::class, 2], [EvalInteger::class, 3], [EvalInteger::class, 4], [EvalInteger::class, 5], [EvalInteger::class, 6]],
        [Code::CONSTANT->make(0), Code::CONSTANT->make(1), Code::ADD->make(), Code::CONSTANT->make(2), Code::CONSTANT->make(3), Code::SUB->make(), Code::CONSTANT->make(4), Code::CONSTANT->make(5), Code::MUL->make(), Code::ARRAY->make(3), Code::POP->make()],
    ],
    [
        '{}',
        [],
        [Code::HASH->make(0), Code::POP->make()],
    ],
    [
        '{1: 2, 3: 4, 5: 6}',
        [[EvalInteger::class, 1], [EvalInteger::class, 2], [EvalInteger::class, 3], [EvalInteger::class, 4], [EvalInteger::class, 5], [EvalInteger::class, 6]],
        [Code::CONSTANT->make(0), Code::CONSTANT->make(1), Code::CONSTANT->make(2), Code::CONSTANT->make(3), Code::CONSTANT->make(4), Code::CONSTANT->make(5), Code::HASH->make(6), Code::POP->make()],
    ],
    [
        '{1: 2 + 3, 4: 5 * 6}',
        [[EvalInteger::class, 1], [EvalInteger::class, 2], [EvalInteger::class, 3], [EvalInteger::class, 4], [EvalInteger::class, 5], [EvalInteger::class, 6]],
        [Code::CONSTANT->make(0), Code::CONSTANT->make(1), Code::CONSTANT->make(2), Code::ADD->make(), Code::CONSTANT->make(3), Code::CONSTANT->make(4), Code::CONSTANT->make(5), Code::MUL->make(), Code::HASH->make(4), Code::POP->make()],
    ],
    [
        '[1, 2, 3][1 + 1]',
        [[EvalInteger::class, 1], [EvalInteger::class, 2], [EvalInteger::class, 3], [EvalInteger::class, 1], [EvalInteger::class, 1]],
        [Code::CONSTANT->make(0), Code::CONSTANT->make(1), Code::CONSTANT->make(2), Code::ARRAY->make(3), Code::CONSTANT->make(3), Code::CONSTANT->make(4), Code::ADD->make(), Code::INDEX->make(), Code::POP->make()],
    ],
    [
        '{1: 2}[2 - 1]',
        [[EvalInteger::class, 1], [EvalInteger::class, 2], [EvalInteger::class, 2], [EvalInteger::class, 1]],
        [Code::CONSTANT->make(0), Code::CONSTANT->make(1), Code::HASH->make(2), Code::CONSTANT->make(2), Code::CONSTANT->make(3), Code::SUB->make(), Code::INDEX->make(), Code::POP->make()],
    ],
    [
        'fn() { return 5 + 10 }',
        [[EvalInteger::class, 5], [EvalInteger::class, 10], [EvalCompiledFunction::class, [Code::CONSTANT->make(0), Code::CONSTANT->make(1), Code::ADD->make(), Code::RETURN_VALUE->make()]]],
        [Code::CONSTANT->make(2), Code::POP->make()],
    ],
    [
        'fn() { 5 + 10 }',
        [[EvalInteger::class, 5], [EvalInteger::class, 10], [EvalCompiledFunction::class, [Code::CONSTANT->make(0), Code::CONSTANT->make(1), Code::ADD->make(), Code::RETURN_VALUE->make()]]],
        [Code::CONSTANT->make(2), Code::POP->make()],
    ],
    [
        'fn() { 1; 2 }',
        [[EvalInteger::class, 1], [EvalInteger::class, 2], [EvalCompiledFunction::class, [Code::CONSTANT->make(0), Code::POP->make(), Code::CONSTANT->make(1), Code::RETURN_VALUE->make()]]],
        [Code::CONSTANT->make(2), Code::POP->make()],
    ],
    [
        'fn() { }',
        [[EvalCompiledFunction::class, [Code::RETURN->make()]]],
        [Code::CONSTANT->make(0), Code::POP->make()],
    ],
]);

it('stringyfies', function () {
    $instructions = [
        Code::ADD->make(),
        Code::CONSTANT->make(2),
        Code::CONSTANT->make(65535),
    ];

    $expected = '0000 ADD
0001 CONSTANT 2
0004 CONSTANT 65535
';

    $concatted = Instructions::merge(...$instructions);
    expect($concatted->string())->toBe($expected, json_encode($concatted));
});

it('reads', function (Code $code, $operands, $bytesRead) {
    $instruction = $code->make(...$operands);

    $definition = $code->definition();

    list($operandsRead, $n) = Code::readOperands($definition, $instruction->slice(1));
    expect($n)->toBe($bytesRead);
    foreach ($operands as $i => $operand) {
        expect($operandsRead[$i])->toBe($operand);
    }
})->with([
    [Code::CONSTANT, [65535], 2],
]);

it('scopes', function () {
    $compiler = new Compiler();
    expect($compiler->scopeIndex)->toBe(0);

    $compiler->emit(Code::MUL);

    $compiler->enterScope();
    expect($compiler->scopeIndex)->toBe(1);

    $compiler->emit(Code::SUB);
    expect($compiler->scopes[$compiler->scopeIndex]->instructions->count())->toBe(1);

    $last = $compiler->scopes[$compiler->scopeIndex]->lastInstruction;
    expect($last->code)->toBe(Code::SUB);

    $compiler->leaveScope();

    $compiler->emit(Code::ADD);

    expect($compiler->scopes[$compiler->scopeIndex]->instructions->count())->toBe(2);

    $last = $compiler->scopes[$compiler->scopeIndex]->lastInstruction;
    expect($last->code)->toBe(Code::ADD);

    $previous = $compiler->scopes[$compiler->scopeIndex]->previousInstruction;
    expect($previous->code)->toBe(Code::MUL);
});
