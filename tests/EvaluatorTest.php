<?php

use Monkey\Evaluator\Evaluator;
use Monkey\Object\Environment;
use Monkey\Object\EvalArray;
use Monkey\Object\EvalBoolean;
use Monkey\Object\EvalError;
use Monkey\Object\EvalFunction;
use Monkey\Object\EvalHash;
use Monkey\Object\EvalInteger;
use Monkey\Object\EvalMacro;
use Monkey\Object\EvalNull;
use Monkey\Object\EvalQuote;
use Monkey\Object\EvalString;

it('evaluates', function ($input, $eval, $value) {
    $program = createProgram($input);
    $environment = new Environment();

    $evaluator = new Evaluator($environment);
    $evaluated = $evaluator->eval($program);
    expect($evaluated)->toBeInstanceOf($eval);
    match ($eval) {
        EvalArray::class => call_user_func(function () use ($evaluated, $value) {
            expect($evaluated->elements)->toHaveCount(count($value));
            foreach ($evaluated->elements as $i => $element) {
                expect($element->value)->toBe($value[$i]);
            }
        }),
        EvalHash::class => call_user_func(function () use ($evaluated, $value) {
            expect($evaluated->pairs)->toHaveCount(count($value));
            foreach ($evaluated->pairs as $key => $pair) {
                expect(array_key_exists($key, $value))->toBeTrue();
                expect($pair[1]->value)->toBe($value[$key]);
            }
        }),
        EvalNull::class => null,
        default => expect($evaluated->value)->toBe($value),
    };
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
    'string 1' => ['"Hello World!"', EvalString::class, 'Hello World!'],
    'string 2' => ['"Hello" + " " + "World!"', EvalString::class, 'Hello World!'],
    'array 1' => ['[1, 2 * 2, 3 + 3]', EvalArray::class, [1, 4, 6]],
    'hash 1' => ['{"one": 10 - 9, 2: true, false: "thr" + "ee"}', EvalHash::class, ['STRING:one' => 1, 'INTEGER:2' => true, 'BOOLEAN:false' => 'three']],
]);

it('handles errors', function ($input, $error) {
    $program = createProgram($input);
    $environment = new Environment();

    $evaluator = new Evaluator($environment);
    $evaluated = $evaluator->eval($program);
    expect($evaluated)->toBeInstanceOf(EvalError::class);
    expect($evaluated->message)->toBe($error);
})->with([
    'type mismtach 1' => ['5 + true', 'type mismatch: INTEGER + BOOLEAN'],
    'type mismtach 2' => ['5 + true; 5;', 'type mismatch: INTEGER + BOOLEAN'],
    'unknown operator 1' => ['-true', 'unknown operator: -BOOLEAN'],
    'unknown operator 2' => ['true + false', 'unknown operator: BOOLEAN + BOOLEAN'],
    'unknown operator 3' => ['5; true + false; 5;', 'unknown operator: BOOLEAN + BOOLEAN'],
    'unknown operator 4' => ['if (10 > 1) { true + false; }', 'unknown operator: BOOLEAN + BOOLEAN'],
    'unknown operator 5' => ['"hello" - "world"', 'unknown operator: STRING - STRING'],
    'identifier 1' => ['foobar', 'identifier not found: foobar'],
    'hash key 1' => ['{[1,2]: "monkey"}', 'unusable as hash key: ARRAY'],
    'hash key 2' => ['{"name": "monkey"}[fn(x) { x }]', 'unusable as hash key: FUNCTION'],
]);

it('evaluates functions', function () {
    $program = createProgram('fn(x) { x + 2; };');
    $environment = new Environment();

    $evaluator = new Evaluator($environment);
    $evaluated = $evaluator->eval($program);
    expect($evaluated)->toBeInstanceOf(EvalFunction::class);
    expect($evaluated->parameters)->toHaveCount(1);
    expect($evaluated->parameters[0])->toBeIdentifier('x');
    expect($evaluated->body->string())->toBe('(x + 2)');
});

it('evaluates closures', function () {
    $program = createProgram('
        let newAdder = fn(x) {
            fn(y) { x + y };
        };

        let addTwo = newAdder(2);
        addTwo(2);
    ');
    $environment = new Environment();

    $evaluator = new Evaluator($environment);
    $evaluated = $evaluator->eval($program);
    expect($evaluated)->toBeInstanceOf(EvalInteger::class);
    expect($evaluated->value)->toBe(4);
});

it('evaluates builtin functions', function ($input, $eval, $value) {
    $program = createProgram($input);
    $environment = new Environment();

    $evaluator = new Evaluator($environment);
    $evaluated = $evaluator->eval($program);
    expect($evaluated)->toBeInstanceOf($eval);
    match ($eval) {
        EvalInteger::class => expect($evaluated->value)->toBe($value),
        EvalError::class => expect($evaluated->message)->toBe($value),
    };
})->with([
    'len 1' => ['len("")', EvalInteger::class, 0],
    'len 2' => ['len("four")', EvalInteger::class, 4],
    'len 3' => ['len("hello world")', EvalInteger::class, 11],
    'len 4' => ['len(1)', EvalError::class, 'argument to `len` not supported, got INTEGER'],
    'len 5' => ['len("one", "two")', EvalError::class, 'wrong number of arguments: got 2, wanted 1'],
]);

it('evaluates array index', function ($input, $value) {
    $program = createProgram($input);
    $environment = new Environment();

    $evaluator = new Evaluator($environment);
    $evaluated = $evaluator->eval($program);
    match ($value) {
        null => expect($evaluated)->toBeInstanceOf(EvalNull::class),
        default => expect($evaluated->value)->toBe($value),
    };
})->with([
    ['[1, 2, 3][0]', 1],
    ['[1, 2, 3][1]', 2],
    ['[1, 2, 3][2]', 3],
    ['let i = 0; [1][i]', 1],
    ['[1, 2, 3][1 + 1]', 3],
    ['let myArray = [1, 2, 3]; myArray[2]', 3],
    ['let myArray = [1, 2, 3]; myArray[0] + myArray[1] + myArray[2]', 6],
    ['let myArray = [1, 2, 3]; let i = myArray[0]; myArray[i]', 2],
    ['[1, 2, 3][3]', null],
    ['[1, 2, 3][-1]', null],
]);

it('evaluates hash index', function ($input, $value) {
    $program = createProgram($input);
    $environment = new Environment();

    $evaluator = new Evaluator($environment);
    $evaluated = $evaluator->eval($program);
    match ($value) {
        null => expect($evaluated)->toBeInstanceOf(EvalNull::class),
        default => expect($evaluated->value)->toBe($value),
    };
})->with([
    ['{"foo": 5}["foo"]', 5],
    ['{"foo": 5}["bar"]', null],
    ['let key = "foo"; {"foo": 5}[key]', 5],
    ['{}["foo"]', null],
    ['{5: 5}[5]', 5],
    ['{true: 5}[true]', 5],
    ['{false: 5}[false]', 5],
]);

it('evaluates quote', function ($input, $node) {
    $program = createProgram($input);
    $environment = new Environment();

    $evaluator = new Evaluator($environment);
    $evaluated = $evaluator->eval($program);
    expect($evaluated)->toBeInstanceOf(EvalQuote::class);
    expect($evaluated->node)->not->toBeNull();
    expect($evaluated->node->string())->toBe($node);
})->with([
    ['quote(5)', '5'],
    ['quote(5 + 8)', '(5 + 8)'],
    ['quote(foobar)', 'foobar'],
    ['quote(foobar + barfoo)', '(foobar + barfoo)'],
    ['quote(unquote(4))', '4'],
    ['quote(unquote(4 + 4))', '8'],
    ['quote(8 + unquote(4 + 4))', '(8 + 8)'],
    ['quote(unquote(4 + 4) + 8)', '(8 + 8)'],
    ['let foobar = 8; quote(foobar)', 'foobar'],
    ['let foobar = 8; quote(unquote(foobar))', '8'],
    ['quote(unquote(true))', 'true'],
    ['quote(unquote(true == false))', 'false'],
    ['quote(unquote(quote(4 + 4)))', '(4 + 4)'],
    ['let quoted = quote(4 + 4); quote(unquote(4 + 4) + unquote(quoted))', '(8 + (4 + 4))']
]);

it('defines macros', function () {
    $program = createProgram('
        let number = 1;
        let function  = fn(x, y) { x + y; };
        let mymacro = macro(x, y) { x + y; };
    ');
    $environment = new Environment();
    $environment->defineMacros($program);

    expect($program->statements)->toHaveCount(2);
    expect($environment->get('number'))->toBeNull();
    expect($environment->get('function'))->toBeNull();

    /** @var EvalMacro $macro */
    $macro = $environment->get('mymacro');
    expect($environment->get('mymacro'))->not->toBeNull();
    expect($macro)->toBeInstanceOf(EvalMacro::class);
    expect($macro->parameters)->toHaveCount(2);
    expect($macro->parameters[0]->string())->toBe('x');
    expect($macro->parameters[1]->string())->toBe('y');
    expect($macro->body->string())->toBe('(x + y)');
});

it('expands macros', function ($input, $expected) {
    $expected = createProgram($expected);
    $program = createProgram($input);
    $environment = new Environment();
    $environment->defineMacros($program);

    $expanded = $environment->expandMacros($program);

    expect($expanded->string())->toBe($expected->string());
})->with([
    [
        '
        let infixExpression = macro() { quote(1 + 2); };
        infixExpression();
        ',
        '(1 + 2)',
    ],
    [
        '
        let reverse = macro(a, b) { quote(unquote(b) - unquote(a)); };
        reverse(2 + 2, 10 - 5);
        ',
        '(10 - 5) - (2 + 2)',
    ],
    [
        '
        let unless = macro(condition, consequence, alternative) {
            quote(if (!(unquote(condition))) {
                unquote(consequence);
            } else {
                unquote(alternative);
            });
        };

        unless(10 > 5, puts("not greater"), puts("greater"));
        ',
        'if (!(10 > 5)) { puts("not greater") } else { puts("greater") }',
    ]
]);
