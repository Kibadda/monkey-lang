<?php

use Monkey\Compiler\Compiler;
use Monkey\Object\EvalArray;
use Monkey\Object\EvalBoolean;
use Monkey\Object\EvalHash;
use Monkey\Object\EvalInteger;
use Monkey\Object\EvalNull;
use Monkey\Object\EvalString;
use Monkey\VM\VM;

it('runs correctly', function (string $input, $expected) {
    $vm = runVM($input);

    $stackElem = $vm->lastPoppedStackElem();

    expect($stackElem)->toBeInstanceOf($expected[0], print_r($stackElem, true));
    if ($expected[0] == EvalArray::class) {
        expect($stackElem->elements)->toHaveCount(count($expected[1]));
        foreach ($stackElem->elements as $i => $element) {
            expect($element->value)->toBe($expected[1][$i]);
        }
    } else if ($expected[0] == EvalHash::class) {
        expect($stackElem->pairs)->toHaveCount(count($expected[1]));
        foreach ($stackElem->pairs as $key => $pair) {
            expect(array_key_exists($key, $expected[1]))->toBeTrue();
            expect($pair->value)->toBe($expected[1][$key]);
        }
    } else if ($expected[0] != EvalNull::class) {
        expect($stackElem->value)->toBe($expected[1]);
    }
})->with([
    ['1', [EvalInteger::class, 1]],
    ['2', [EvalInteger::class, 2]],
    ['1 + 2', [EvalInteger::class, 3]],
    ['1 - 2', [EvalInteger::class, -1]],
    ['4 / 2', [EvalInteger::class, 2]],
    ['50 / 2 * 2 + 10 - 5', [EvalInteger::class, 55]],
    ['5 + 5 + 5 + 5 - 10', [EvalInteger::class, 10]],
    ['2 * 2 * 2 * 2 * 2', [EvalInteger::class, 32]],
    ['5 * 2 + 10', [EvalInteger::class, 20]],
    ['5 + 2 * 10', [EvalInteger::class, 25]],
    ['5 * (2 + 10)', [EvalInteger::class, 60]],
    ['true', [EvalBoolean::class, true]],
    ['false', [EvalBoolean::class, false]],
    ['1 < 2', [EvalBoolean::class, true]],
    ['1 > 2', [EvalBoolean::class, false]],
    ['1 < 1', [EvalBoolean::class, false]],
    ['1 > 1', [EvalBoolean::class, false]],
    ['1 == 1', [EvalBoolean::class, true]],
    ['1 != 1', [EvalBoolean::class, false]],
    ['1 == 2', [EvalBoolean::class, false]],
    ['1 != 2', [EvalBoolean::class, true]],
    ['true == true', [EvalBoolean::class, true]],
    ['false == false', [EvalBoolean::class, true]],
    ['true == false', [EvalBoolean::class, false]],
    ['true != false', [EvalBoolean::class, true]],
    ['false != true', [EvalBoolean::class, true]],
    ['(1 < 2) == true', [EvalBoolean::class, true]],
    ['(1 < 2) == false', [EvalBoolean::class, false]],
    ['(1 > 2) == true', [EvalBoolean::class, false]],
    ['(1 > 2) == false', [EvalBoolean::class, true]],
    ['-5', [EvalInteger::class, -5]],
    ['-10', [EvalInteger::class, -10]],
    ['-50 + 100 + -50', [EvalInteger::class, 0]],
    ['(5 + 10 * 2 + 15 /3) * 2 + -10', [EvalInteger::class, 50]],
    ['!true', [EvalBoolean::class, false]],
    ['!false', [EvalBoolean::class, true]],
    ['!5', [EvalBoolean::class, false]],
    ['!!true', [EvalBoolean::class, true]],
    ['!!false', [EvalBoolean::class, false]],
    ['!!5', [EvalBoolean::class, true]],
    ['if (true) { 10 }', [EvalInteger::class, 10]],
    ['if (true) { 10 } else { 20 }', [EvalInteger::class, 10]],
    ['if (false) { 10 } else { 20 }', [EvalInteger::class, 20]],
    ['if (1) { 10 }', [EvalInteger::class, 10]],
    ['if (1 < 2) { 10 }', [EvalInteger::class, 10]],
    ['if (1 < 2) { 10 } else { 20 }', [EvalInteger::class, 10]],
    ['if (1 > 2) { 10 } else { 20 }', [EvalInteger::class, 20]],
    ['if (1 > 2) { 10 }', [EvalNull::class]],
    ['if (false) { 10 }', [EvalNull::class]],
    ['!(if (false) { 5; })', [EvalBoolean::class, true]],
    ['if ((if (false) { 10 })) { 10 } else { 20 }', [EvalInteger::class, 20]],
    ['let one = 1; one', [EvalInteger::class, 1]],
    ['let one = 1; let two = 2; one + two', [EvalInteger::class, 3]],
    ['let one = 1; let two = one + one; one + two', [EvalInteger::class, 3]],
    ['"monkey"', [EvalString::class, 'monkey']],
    ['"mon" + "key"', [EvalString::class, 'monkey']],
    ['"mon" + "key" + "banana"', [EvalString::class, 'monkeybanana']],
    ['[]', [EvalArray::class, []]],
    ['[1, 2, 3]', [EvalArray::class, [1, 2, 3]]],
    ['[1 + 2, 3 * 4, 5 + 6]', [EvalArray::class, [3, 12, 11]]],
    ['{}', [EvalHash::class, []]],
    ['{1: 2, 2: 3}', [EvalHash::class, ['INTEGER:1' => 2, 'INTEGER:2' => 3]]],
    ['{1 + 1: 2 * 2, 3 + 3: 4 * 4}', [EvalHash::class, ['INTEGER:2' => 4, 'INTEGER:6' => 16]]],
    ['[1, 2, 3][1]', [EvalInteger::class, 2]],
    ['[1, 2, 3][0 + 2]', [EvalInteger::class, 3]],
    ['[[1, 1, 1]][0][0]', [EvalInteger::class, 1]],
    ['[][0]', [EvalNull::class]],
    ['[1, 2, 3][99]', [EvalNull::class]],
    ['[1][-1]', [EvalNull::class]],
    ['{1: 1, 2: 2}[1]', [EvalInteger::class, 1]],
    ['{1: 1, 2: 2}[2]', [EvalInteger::class, 2]],
    ['{1: 1}[0]', [EvalNull::class]],
    ['{}[0]', [EvalNull::class]],
    ['let fivePlusTen = fn() { 5 + 10 }; fivePlusTen()', [EvalInteger::class, 15]],
    ['let one = fn() { 1 }; let two = fn() { 2 }; one() + two()', [EvalInteger::class, 3]],
    ['let a = fn() { 1 }; let b = fn() { a() + 1 }; let c = fn() { b() + 1 }; c()', [EvalInteger::class, 3]],
    ['let earlyExit = fn() { return 99; 100 }; earlyExit()', [EvalInteger::class, 99]],
    ['let earlyExit = fn() { return 99; return 100 }; earlyExit()', [EvalInteger::class, 99]],
    ['let noReturn = fn() {}; noReturn()', [EvalNull::class]],
    ['let noReturn = fn() {}; let noReturnTwo = fn() { noReturn() }; noReturn(); noReturnTwo()', [EvalNull::class]],
    ['let retunsOne = fn() { 1 }; let returnsOneReturner = fn() { retunsOne }; returnsOneReturner()()', [EvalInteger::class, 1]],
    ['let one = fn() { let one = 1; one }; one()', [EvalInteger::class, 1]],
    ['let oneAndTwo = fn() { let one = 1; let two = 2; one + two }; oneAndTwo()', [EvalInteger::class, 3]],
    ['let oneAndTwo = fn() { let one = 1; let two = 2; one + two }; let threeAndFour = fn() { let three = 3; let four = 4; three + four }; oneAndTwo() + threeAndFour()', [EvalInteger::class, 10]],
    ['let firstFoobar = fn() { let foobar = 50; foobar }; let secondFoobar = fn() { let foobar = 100; foobar }; firstFoobar() + secondFoobar()', [EvalInteger::class, 150]],
    ['let globalSeed = 50; let minusOne = fn() { let num = 1; globalSeed - num }; let minusTwo = fn() { let num = 2; globalSeed - num }; minusOne() + minusTwo()', [EvalInteger::class, 97]],
    ['let returnsOneReturner = fn() { let returnsOne = fn() { 1 }; returnsOne }; returnsOneReturner()()', [EvalInteger::class, 1]],
    ['let identity = fn(a) { a }; identity(4)', [EvalInteger::class, 4]],
    ['let sum = fn(a, b) { a + b }; sum(1, 2)', [EvalInteger::class, 3]],
    ['let sum = fn(a, b) { let c = a + b; c }; sum(1, 2)', [EvalInteger::class, 3]],
    ['let sum = fn(a, b) { let c = a + b; c }; sum(1, 2) + sum(3, 4)', [EvalInteger::class, 10]],
    ['let sum = fn(a, b) { let c = a + b; c }; let outer = fn() { sum(1, 2) + sum(3, 4) }; outer()', [EvalInteger::class, 10]],
    ['let globalNum = 10; let sum = fn(a, b) { let c = a + b; c + globalNum }; let outer = fn() { sum(1, 2) + sum(3, 4) + globalNum }; outer() + globalNum', [EvalInteger::class, 50]],
    ['match (1) { 1 -> true }', [EvalBoolean::class, true]],
    ['match (2) { 2 -> 1 }', [EvalInteger::class, 1]],
    ['match (true) { true -> 1 }', [EvalInteger::class, 1]],
    ['match ("on" + "e") { "one" -> 1 }', [EvalInteger::class, 1]],
    ['match (true) { 1 > 2 -> 2, 1 < 2 -> 1 }', [EvalInteger::class, 1]],
    ['match (1) { 2 -> 1 }', [EvalNull::class]],
    ['match (5 - 4) { 3 - 2 -> 1 + 1 }', [EvalInteger::class, 2]],
    ['match (fn() { true }()) { 1 != 2 -> 1 + 1 }', [EvalInteger::class, 2]],
]);

it('errors', function ($input, $message) {
    $program = createProgram($input);

    $compiler = new Compiler();
    expect($compiler->compile($program))->not->toThrow(Exception::class);

    $vm = new VM($compiler);
    try {
        $vm->run();
        expect(false)->toBeTrue('expected to throw an exception');
    } catch (Exception $exception) {
        expect($exception->getMessage())->toBe($message);
    }
})->with([
    ['fn() { 1 }(1)', 'wrong number of arguments: want=0, got=1'],
    ['fn(a) { a }()', 'wrong number of arguments: want=1, got=0'],
    ['fn(a, b) { a + b }(1)', 'wrong number of arguments: want=2, got=1'],
]);
