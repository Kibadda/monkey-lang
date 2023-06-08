<?php

use Monkey\Compiler\Compiler;
use Monkey\Evaluator\Object\EvalArray;
use Monkey\Evaluator\Object\EvalBoolean;
use Monkey\Evaluator\Object\EvalHash;
use Monkey\Evaluator\Object\EvalInteger;
use Monkey\Evaluator\Object\EvalNull;
use Monkey\Evaluator\Object\EvalString;
use Monkey\VM\VM;

function runVM(string $input): VM
{
    $program = createProgram($input);

    $compiler = new Compiler();
    expect($compiler->compile($program))->not->toThrow(Exception::class);

    $vm = VM::new($compiler);
    expect($vm->run())->not->toThrow(Exception::class);

    return $vm;
}

it('runs correctly', function (string $input, $expected) {
    $vm = runVM($input);

    $stackElem = $vm->lastPoppedStackElem();

    expect($stackElem)->toBeInstanceOf($expected[0]);
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
]);
