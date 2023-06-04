<?php

use Monkey\Evaluator\Environment;
use Monkey\Evaluator\Evaluator;
use Monkey\Evaluator\Object\EvalBoolean;
use Monkey\Evaluator\Object\EvalError;
use Monkey\Evaluator\Object\EvalFunction;
use Monkey\Evaluator\Object\EvalInteger;
use Monkey\Evaluator\Object\EvalNull;

it('evaluates', function ($input, $eval, $value) {
    $program = createProgram($input);
    $environment = Environment::new();

    $evaluated = Evaluator::new($environment)->eval($program);
    expect($evaluated)->toBeInstanceOf($eval);
    if ($eval != EvalNull::class) {
        expect($evaluated->value)->toBe($value);
    }
})->with([
    'literal integer 1' => ['5', EvalInteger::class, 5],
    'literal integer 2' => ['10', EvalInteger::class, 10],
    'literal boolean 1' => ['true', EvalBoolean::class, true],
    'literal boolean 2' => ['false', EvalBoolean::class, false],
    'prefix bang 1' => ['!true', EvalBoolean::class, false],
    'prefix bang 2' => ['!false', EvalBoolean::class, true],
    'prefix bang 3' => ['!5', EvalBoolean::class, false],
    'prefix bang 4' => ['!!true', EvalBoolean::class, true],
    'prefix bang 5' => ['!!false', EvalBoolean::class, false],
    'prefix bang 6' => ['!!5', EvalBoolean::class, true],
    'prefix minus 1' => ['-5', EvalInteger::class, -5],
    'prefix minus 2' => ['-10', EvalInteger::class, -10],
    'infix plus' => ['5 + 5', EvalInteger::class, 10],
    'infix minus' => ['5 - 5', EvalInteger::class, 0],
    'infix slash' => ['5 / 5', EvalInteger::class, 1],
    'infix asterisk' => ['5 * 5', EvalInteger::class, 25],
    'infix lt' => ['1 < 2', EvalBoolean::class, true],
    'infix gt' => ['1 > 2', EvalBoolean::class, false],
    'infix equals 1' => ['1 == 2', EvalBoolean::class, false],
    'infix equals 2' => ['true == true', EvalBoolean::class, true],
    'infix equals 3' => ['false == false', EvalBoolean::class, true],
    'infix equals 4' => ['true == false', EvalBoolean::class, false],
    'infix not equals 1' => ['1 != 2', EvalBoolean::class, true],
    'infix not equals 2' => ['true != false', EvalBoolean::class, true],
    'infix not equals 3' => ['false != true', EvalBoolean::class, true],
    'if 1' => ['if (true) { 10; };', EvalInteger::class, 10],
    'if 2' => ['if (false) { 10; };', EvalNull::class, null],
    'if 3' => ['if (1) { 10; };', EvalInteger::class, 10],
    'if 4' => ['if (1 < 2) { 10; };', EvalInteger::class, 10],
    'if 5' => ['if (1 > 2) { 10; };', EvalNull::class, null],
    'if 6' => ['if (1 > 2) { 10; } else { 20; };', EvalInteger::class, 20],
    'if 7' => ['if (1 < 2) { 10; } else { 20; };', EvalInteger::class, 10],
    'return 1' => ['return 10;', EvalInteger::class, 10],
    'return 2' => ['return 10; 9;', EvalInteger::class, 10],
    'return 3' => ['return 2 * 5; 9;', EvalInteger::class, 10],
    'return 4' => ['9; return 10; 9;', EvalInteger::class, 10],
    'return 5' => ['if (10 > 1) { if (10 > 1) { return 10; }; return 1; }', EvalInteger::class, 10],
    'let 1' => ['let a = 5; a;', EvalInteger::class, 5],
    'let 2' => ['let a = 5 * 5; a;', EvalInteger::class, 25],
    'let 3' => ['let a = 5; let b = a; b;', EvalInteger::class, 5],
    'let 4' => ['let a = 5; let b = a; let c = a + b + 5; c;', EvalInteger::class, 15],
    'function 1' => ['let identity = fn(x) { x; }; identity(5);', EvalInteger::class, 5],
    'function 2' => ['let identity = fn(x) { return x; }; identity(5);', EvalInteger::class, 5],
    'function 3' => ['let double = fn(x) { x * 2; }; double(5);', EvalInteger::class, 10],
    'function 4' => ['let add = fn(x, y) { x + y; }; add(5, 5);', EvalInteger::class, 10],
    'function 5' => ['let add = fn(x, y) { x + y; }; add(5 + 5, add(5, 5));', EvalInteger::class, 20],
    'function 6' => ['fn(x) { x; }(5)', EvalInteger::class, 5],
]);

it('handles errors', function ($input, $error) {
    $program = createProgram($input);
    $environment = Environment::new();

    $evaluated = Evaluator::new($environment)->eval($program);
    expect($evaluated)->toBeInstanceOf(EvalError::class);
    expect($evaluated->message)->toBe($error);
})->with([
    'type mismtach 1' => ['5 + true', 'type mismatch: INTEGER + BOOLEAN'],
    'type mismtach 2' => ['5 + true; 5;', 'type mismatch: INTEGER + BOOLEAN'],
    'unknown operator 1' => ['-true', 'unknown operator: -BOOLEAN'],
    'unknown operator 2' => ['true + false', 'unknown operator: BOOLEAN + BOOLEAN'],
    'unknown operator 3' => ['5; true + false; 5;', 'unknown operator: BOOLEAN + BOOLEAN'],
    'unknown operator 4' => ['if (10 > 1) { true + false; }', 'unknown operator: BOOLEAN + BOOLEAN'],
    'identifier 1' => ['foobar', 'identifier not found: foobar'],
]);

it('evaluates functions', function () {
    $program = createProgram('fn(x) { x + 2; };');
    $environment = Environment::new();

    $evaluated = Evaluator::new($environment)->eval($program);
    expect($evaluated)->toBeInstanceOf(EvalFunction::class);
    expect($evaluated->parameters)->toHaveCount(1);
    expect($evaluated->parameters[0])->toBeIdentifier('x');
    expect($evaluated->body->string())->toBe('(x + 2)');
});
