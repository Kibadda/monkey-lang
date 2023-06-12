<?php

use Monkey\Code\Code;
use Monkey\Compiler\Compiler;
use Monkey\Compiler\Instructions;
use Monkey\Object\EvalCompiledFunction;
use Monkey\Object\EvalInteger;
use Monkey\Object\EvalString;

it('compiles', function (string $input, array $expectedConstants, array $expectedInstructions) {
    $program = createProgram($input);

    $compiler = new Compiler();

    expect($compiler->compile($program))->not->toThrow(Exception::class);
    $instructions = new Instructions($expectedInstructions);
    expect($compiler->currentInstructions()->string())->toBe($instructions->string());
    expect($compiler->currentInstructions()->count())->toBe($instructions->count());
    foreach ($compiler->currentInstructions() as $i => $instruction) {
        expect($instruction)->toBe($instructions[$i]);
    }
    foreach ($compiler->constants as $i => $constant) {
        expect($constant)->toBeInstanceOf($expectedConstants[$i][0]);
        if ($constant instanceof EvalCompiledFunction) {
            $con = new Instructions($expectedConstants[$i][1]);
            expect($constant->instructions->string())->toBe($con->string());
            expect($constant->instructions->count())->toBe($con->count());
            foreach ($constant->instructions as $j => $instruction) {
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
        [Code::CLOSURE->make(2, 0), Code::POP->make()],
    ],
    [
        'fn() { 5 + 10 }',
        [[EvalInteger::class, 5], [EvalInteger::class, 10], [EvalCompiledFunction::class, [Code::CONSTANT->make(0), Code::CONSTANT->make(1), Code::ADD->make(), Code::RETURN_VALUE->make()]]],
        [Code::CLOSURE->make(2, 0), Code::POP->make()],
    ],
    [
        'fn() { 1; 2 }',
        [[EvalInteger::class, 1], [EvalInteger::class, 2], [EvalCompiledFunction::class, [Code::CONSTANT->make(0), Code::POP->make(), Code::CONSTANT->make(1), Code::RETURN_VALUE->make()]]],
        [Code::CLOSURE->make(2, 0), Code::POP->make()],
    ],
    [
        'fn() { }',
        [[EvalCompiledFunction::class, [Code::RETURN->make()]]],
        [Code::CLOSURE->make(0, 0), Code::POP->make()],
    ],
    [
        'fn () { 24 }()',
        [[EvalInteger::class, 24], [EvalCompiledFunction::class, [Code::CONSTANT->make(0), Code::RETURN_VALUE->make()]]],
        [Code::CLOSURE->make(1, 0), Code::CALL->make(0), Code::POP->make()],
    ],
    [
        'let noArg = fn() { 24 }; noArg()',
        [[EvalInteger::class, 24], [EvalCompiledFunction::class, [Code::CONSTANT->make(0), Code::RETURN_VALUE->make()]]],
        [Code::CLOSURE->make(1, 0), Code::SET_GLOBAL->make(0), Code::GET_GLOBAL->make(0), Code::CALL->make(0), Code::POP->make()],
    ],
    [
        'let num = 55; fn() { num }',
        [[EvalInteger::class, 55], [EvalCompiledFunction::class, [Code::GET_GLOBAL->make(0), Code::RETURN_VALUE->make()]]],
        [Code::CONSTANT->make(0), Code::SET_GLOBAL->make(0), Code::CLOSURE->make(1, 0), Code::POP->make()],
    ],
    [
        'fn() { let num = 55; num }',
        [[EvalInteger::class, 55], [EvalCompiledFunction::class, [Code::CONSTANT->make(0), Code::SET_LOCAL->make(0), Code::GET_LOCAL->make(0), Code::RETURN_VALUE->make()]]],
        [Code::CLOSURE->make(1, 0), Code::POP->make()],
    ],
    [
        'fn() { let a = 55; let b = 77; a + b }',
        [[EvalInteger::class, 55], [EvalInteger::class, 77], [EvalCompiledFunction::class, [Code::CONSTANT->make(0), Code::SET_LOCAL->make(0), Code::CONSTANT->make(1), Code::SET_LOCAL->make(1), Code::GET_LOCAL->make(0), Code::GET_LOCAL->make(1), Code::ADD->make(), Code::RETURN_VALUE->make()]]],
        [Code::CLOSURE->make(2, 0), Code::POP->make()],
    ],
    [
        'let oneArg = fn(a) {}; oneArg(24)',
        [[EvalCompiledFunction::class, [Code::RETURN->make()]], [EvalInteger::class, 24]],
        [Code::CLOSURE->make(0, 0), Code::SET_GLOBAL->make(0), Code::GET_GLOBAL->make(0), Code::CONSTANT->make(1), Code::CALL->make(1), Code::POP->make()],
    ],
    [
        'let manyArg = fn(a, b, c) {}; manyArg(24, 25, 26)',
        [[EvalCompiledFunction::class, [Code::RETURN->make()]], [EvalInteger::class, 24], [EvalInteger::class, 25], [EvalInteger::class, 26]],
        [Code::CLOSURE->make(0, 0), Code::SET_GLOBAL->make(0), Code::GET_GLOBAL->make(0), Code::CONSTANT->make(1), Code::CONSTANT->make(2), Code::CONSTANT->make(3), Code::CALL->make(3), Code::POP->make()],
    ],
    [
        'let oneArg = fn(a) { a }; oneArg(24)',
        [[EvalCompiledFunction::class, [Code::GET_LOCAL->make(0), Code::RETURN_VALUE->make()]], [EvalInteger::class, 24]],
        [Code::CLOSURE->make(0, 0), Code::SET_GLOBAL->make(0), Code::GET_GLOBAL->make(0), Code::CONSTANT->make(1), Code::CALL->make(1), Code::POP->make()],
    ],
    [
        'let manyArg = fn(a, b, c) { a; b; c }; manyArg(24, 25, 26)',
        [[EvalCompiledFunction::class, [Code::GET_LOCAL->make(0), Code::POP->make(), Code::GET_LOCAL->make(1), Code::POP->make(), Code::GET_LOCAL->make(2), Code::RETURN_VALUE->make()]], [EvalInteger::class, 24], [EvalInteger::class, 25], [EvalInteger::class, 26]],
        [Code::CLOSURE->make(0, 0), Code::SET_GLOBAL->make(0), Code::GET_GLOBAL->make(0), Code::CONSTANT->make(1), Code::CONSTANT->make(2), Code::CONSTANT->make(3), Code::CALL->make(3), Code::POP->make()],
    ],
    [
        'match (1) { 1 -> true, 2 -> false, 3 -> "one" }',
        [[EvalInteger::class, 1], [EvalInteger::class, 1], [EvalInteger::class, 1], [EvalInteger::class, 2], [EvalInteger::class, 1], [EvalInteger::class, 3], [EvalString::class, 'one']],
        [Code::CONSTANT->make(0), Code::CONSTANT->make(1), Code::EQUAL->make(), Code::JUMP_NOT_TRUTHY->make(14), Code::TRUE->make(), Code::JUMP->make(45), Code::CONSTANT->make(2), Code::CONSTANT->make(3), Code::EQUAL->make(), Code::JUMP_NOT_TRUTHY->make(28), Code::FALSE->make(), Code::JUMP->make(45), Code::CONSTANT->make(4), Code::CONSTANT->make(5), Code::EQUAL->make(), Code::JUMP_NOT_TRUTHY->make(44), Code::CONSTANT->make(6), Code::JUMP->make(45), Code::NULL->make(), Code::POP->make()],
    ],
    [
        'match (1) { 2 -> true, ? -> false }',
        [[EvalInteger::class, 1], [EvalInteger::class, 2]],
        [Code::CONSTANT->make(0), Code::CONSTANT->make(1), Code::EQUAL->make(), Code::JUMP_NOT_TRUTHY->make(14), Code::TRUE->make(), Code::JUMP->make(15), Code::FALSE->make(), Code::POP->make()],
    ],
    [
        'len([]); push([], 1)',
        [[EvalInteger::class, 1]],
        [Code::GET_BUILTIN->make(0), Code::ARRAY->make(0), Code::CALL->make(1), Code::POP->make(), Code::GET_BUILTIN->make(5), Code::ARRAY->make(0), Code::CONSTANT->make(0), Code::CALL->make(2), Code::POP->make()],
    ],
    [
        'fn() { len([]) }',
        [[EvalCompiledFunction::class, [Code::GET_BUILTIN->make(0), Code::ARRAY->make(0), Code::CALL->make(1), Code::RETURN_VALUE->make()]]],
        [Code::CLOSURE->make(0, 0), Code::POP->make()],
    ],
    [
        'fn(a) { fn(b) { a + b } }',
        [[EvalCompiledFunction::class, [Code::GET_FREE->make(0), Code::GET_LOCAL->make(0), Code::ADD->make(), Code::RETURN_VALUE->make()]], [EvalCompiledFunction::class, [Code::GET_LOCAL->make(0), Code::CLOSURE->make(0, 1), Code::RETURN_VALUE->make()]]],
        [Code::CLOSURE->make(1, 0), Code::POP->make()],
    ],
    [
        'fn(a) { fn(b) { fn(c) { a + b + c } } }',
        [[EvalCompiledFunction::class, [Code::GET_FREE->make(0), Code::GET_FREE->make(1), Code::ADD->make(), Code::GET_LOCAL->make(0), Code::ADD->make(), Code::RETURN_VALUE->make()]], [EvalCompiledFunction::class, [Code::GET_FREE->make(0), Code::GET_LOCAL->make(0), Code::CLOSURE->make(0, 2), Code::RETURN_VALUE->make()]], [EvalCompiledFunction::class, [Code::GET_LOCAL->make(0), Code::CLOSURE->make(1, 1), Code::RETURN_VALUE->make()]]],
        [Code::CLOSURE->make(2, 0), Code::POP->make()],
    ],
    [
        'let global = 55; fn() { let a = 66; fn() { let b = 77; fn() { let c = 88; global + a + b + c } } }',
        [[EvalInteger::class, 55], [EvalInteger::class, 66], [EvalInteger::class, 77], [EvalInteger::class, 88], [EvalCompiledFunction::class, [Code::CONSTANT->make(3), Code::SET_LOCAL->make(0), Code::GET_GLOBAL->make(0), Code::GET_FREE->make(0), Code::ADD->make(), Code::GET_FREE->make(1), Code::ADD->make(), Code::GET_LOCAL->make(0), Code::ADD->make(), Code::RETURN_VALUE->make()]], [EvalCompiledFunction::class, [Code::CONSTANT->make(2), Code::SET_LOCAL->make(0), Code::GET_FREE->make(0), Code::GET_LOCAL->make(0), Code::CLOSURE->make(4, 2), Code::RETURN_VALUE->make()]], [EvalCompiledFunction::class, [Code::CONSTANT->make(1), Code::SET_LOCAL->make(0), Code::GET_LOCAL->make(0), Code::CLOSURE->make(5, 1), Code::RETURN_VALUE->make()]]],
        [Code::CONSTANT->make(0), Code::SET_GLOBAL->make(0), Code::CLOSURE->make(6, 0), Code::POP->make()],
    ],
    [
        'let countDown = fn(x) { countDown(x - 1) }; countDown(1)',
        [[EvalInteger::class, 1], [EvalCompiledFunction::class, [Code::CURRENT_CLOSURE->make(), Code::GET_LOCAL->make(0), Code::CONSTANT->make(0), Code::SUB->make(), Code::CALL->make(1), Code::RETURN_VALUE->make()]], [EvalInteger::class, 1]],
        [Code::CLOSURE->make(1, 0), Code::SET_GLOBAL->make(0), Code::GET_GLOBAL->make(0), Code::CONSTANT->make(2), Code::CALL->make(1), Code::POP->make()],
    ],
    [
        'let wrapper = fn() { let countDown = fn(x) { countDown(x - 1) }; countDown(1) }; wrapper()',
        [[EvalInteger::class, 1], [EvalCompiledFunction::class, [Code::CURRENT_CLOSURE->make(), Code::GET_LOCAL->make(0), Code::CONSTANT->make(0), Code::SUB->make(), Code::CALL->make(1), Code::RETURN_VALUE->make()]], [EvalInteger::class, 1], [EvalCompiledFunction::class, [Code::CLOSURE->make(1, 0), Code::SET_LOCAL->make(0), Code::GET_LOCAL->make(0), Code::CONSTANT->make(2), Code::CALL->make(1), Code::RETURN_VALUE->make()]]],
        [Code::CLOSURE->make(3, 0), Code::SET_GLOBAL->make(0), Code::GET_GLOBAL->make(0), Code::CALL->make(0), Code::POP->make()],
    ],
]);

it('stringyfies', function () {
    $instructions = new Instructions([
        Code::ADD->make(),
        Code::GET_LOCAL->make(1),
        Code::CONSTANT->make(2),
        Code::CONSTANT->make(65535),
        Code::CLOSURE->make(65535, 255),
    ]);

    $expected = '0000 ADD
0001 GET_LOCAL 1
0003 CONSTANT 2
0006 CONSTANT 65535
0009 CLOSURE 65535 255
';

    expect($instructions->string())->toBe($expected, json_encode($instructions));
});

it('reads', function (Code $code, $operands, $bytesRead) {
    $instruction = $code->make(...$operands);

    list($operandsRead, $n) = $code->readOperands($instruction->slice(1));
    expect($n)->toBe($bytesRead);
    foreach ($operands as $i => $operand) {
        expect($operandsRead[$i])->toBe($operand);
    }
})->with([
    [Code::CONSTANT, [65535], 2],
    [Code::GET_LOCAL, [255], 1],
]);

it('scopes', function () {
    $compiler = new Compiler();
    expect($compiler->scopeIndex)->toBe(0);

    $compiler->emit(Code::MUL);

    $globalSymbolTable = $compiler->symbolTable;

    $compiler->enterScope();
    expect($compiler->scopeIndex)->toBe(1);

    $compiler->emit(Code::SUB);
    expect($compiler->scopes[$compiler->scopeIndex]->instructions->count())->toBe(1);

    $last = $compiler->scopes[$compiler->scopeIndex]->lastInstruction;
    expect($last->code)->toBe(Code::SUB);

    expect($compiler->symbolTable->outer)->toBe($globalSymbolTable);

    $compiler->leaveScope();
    expect($compiler->scopeIndex)->toBe(0);

    expect($compiler->symbolTable)->toBe($globalSymbolTable);
    expect($compiler->symbolTable->outer)->toBeNull();

    $compiler->emit(Code::ADD);

    expect($compiler->scopes[$compiler->scopeIndex]->instructions->count())->toBe(2);

    $last = $compiler->scopes[$compiler->scopeIndex]->lastInstruction;
    expect($last->code)->toBe(Code::ADD);

    $previous = $compiler->scopes[$compiler->scopeIndex]->previousInstruction;
    expect($previous->code)->toBe(Code::MUL);
});
