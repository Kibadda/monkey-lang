<?php

use Monkey\Ast\Expression\ArrayLiteral;
use Monkey\Ast\Expression\Boolean;
use Monkey\Ast\Expression\CallExpression;
use Monkey\Ast\Expression\FunctionLiteral;
use Monkey\Ast\Expression\HashLiteral;
use Monkey\Ast\Expression\Identifier;
use Monkey\Ast\Expression\IfExpression;
use Monkey\Ast\Expression\IndexExpression;
use Monkey\Ast\Expression\InfixExpression;
use Monkey\Ast\Expression\IntegerLiteral;
use Monkey\Ast\Expression\MacroLiteral;
use Monkey\Ast\Expression\MatchLiteral;
use Monkey\Ast\Expression\PrefixExpression;
use Monkey\Ast\Expression\StringLiteral;
use Monkey\Ast\Program;
use Monkey\Ast\Statement\LetStatement;
use Monkey\Token\Token;
use Monkey\Token\Type;

it('parses let statements correctly', function ($input, $identifier, $expression) {
    $program = createProgram($input);
    expect($program->statements[0])->toBeLetStatement($identifier, $expression);
})->with([
    'integer' => ['let x = 5;', 'x', [IntegerLiteral::class, 5]],
    'boolean' => ['let y = true;', 'y', [Boolean::class, true]],
    'identifier' => ['let foobar = y;', 'foobar', [Identifier::class, 'y']],
]);

it('parses return statements correctly', function ($input, $expression) {
    $program = createProgram($input);
    expect($program->statements[0])->toBeReturnStatement($expression);
})->with([
    'integer' => ['return 5;', [IntegerLiteral::class, 5]],
    'boolean' => ['return true;', [Boolean::class, true]],
    'identifier' => ['return foobar;', [Identifier::class, 'foobar']],
]);

it('returns right string', function () {
    $program = new Program([
        new LetStatement(
            new Token(Type::LET, 'let'),
            new Identifier(new Token(Type::IDENTIFIER, 'myVar'), 'myVar'),
            new Identifier(new Token(Type::IDENTIFIER, 'anotherVar'), 'anotherVar'),
        ),
    ]);

    expect($program->string())->toBe('let myVar = anotherVar;');
});

it('parses literal expressions', function ($input, $expression, $value) {
    $program = createProgram($input);
    expect($program->statements[0])->toBeExpressionStatement($expression, $value);
})->with([
    'identifier 1' => ['foobar;', Identifier::class, 'foobar'],
    'identifier 2' => ['t;', Identifier::class, 't'],
    'integer 1' => ['5;', IntegerLiteral::class, 5],
    'integer 2' => ['10;', IntegerLiteral::class, 10],
    'boolean 1' => ['true;', Boolean::class, true],
    'boolean 2' => ['false;', Boolean::class, false],
    'string 1' => ['"hello world";', StringLiteral::class, 'hello world'],
    'string 2' => ['"test";', StringLiteral::class, 'test'],
]);

it('parses prefix expression', function ($input, $operator, $expression) {
    $program = createProgram($input);
    expect($program->statements[0])->toBeExpressionStatement(PrefixExpression::class, $operator, $expression);
})->with([
    'bang' => ['!5;', '!', [IntegerLiteral::class, 5]],
    'minus' => ['-15;', '-', [IntegerLiteral::class, 15]],
]);

it('parses infix expression', function ($input, $left, $operator, $right) {
    $program = createProgram($input);
    expect($program->statements[0])->toBeExpressionStatement(InfixExpression::class, $left, $operator, $right);
})->with([
    'plus' => ['5 + 5;', [IntegerLiteral::class, 5], '+', [IntegerLiteral::class, 5]],
    'minus' => ['5 - 5;', [IntegerLiteral::class, 5], '-', [IntegerLiteral::class, 5]],
    'asterisk' => ['5 * 5;', [IntegerLiteral::class, 5], '*', [IntegerLiteral::class, 5]],
    'slash' => ['5 / 5;', [IntegerLiteral::class, 5], '/', [IntegerLiteral::class, 5]],
    'gt' => ['5 > 5;', [IntegerLiteral::class, 5], '>', [IntegerLiteral::class, 5]],
    'lt' => ['5 < 5;', [IntegerLiteral::class, 5], '<', [IntegerLiteral::class, 5]],
    'equals' => ['5 == 5;', [IntegerLiteral::class, 5], '==', [IntegerLiteral::class, 5]],
    'not equals' => ['5 != 5;', [IntegerLiteral::class, 5], '!=', [IntegerLiteral::class, 5]],
]);

it('parses operator precedence', function ($input, $string) {
    $program = createProgram($input);
    expect($program->string())->toBe($string);
})->with([
    ['-a * b', '((-a) * b)'],
    ['!-a', '(!(-a))'],
    ['a + b + c', '((a + b) + c)'],
    ['a + b - c', '((a + b) - c)'],
    ['a * b * c', '((a * b) * c)'],
    ['a * b / c', '((a * b) / c)'],
    ['a + b / c', '(a + (b / c))'],
    ['a + b * c + d / e - f', '(((a + (b * c)) + (d / e)) - f)'],
    ['5 > 4 == 3 < 4', '((5 > 4) == (3 < 4))'],
    ['5 < 4 != 3 > 4', '((5 < 4) != (3 > 4))'],
    ['3 + 4 * 5 == 3 * 1 + 4 * 5', '((3 + (4 * 5)) == ((3 * 1) + (4 * 5)))'],
    ['1 + (2 + 3) + 4', '((1 + (2 + 3)) + 4)'],
    ['(5 + 5) * 2', '((5 + 5) * 2)'],
    ['2 / (5 + 5)', '(2 / (5 + 5))'],
    ['-(5 + 5)', '(-(5 + 5))'],
    ['!(true == true)', '(!(true == true))'],
    ['a + add(b * c) + d', '((a + add((b * c))) + d)'],
    ['add(a, b, 1, 2 * 3, 4 + 5, add(6, 7 * 8))', 'add(a, b, 1, (2 * 3), (4 + 5), add(6, (7 * 8)))'],
    ['add(a + b + c * d / f + g)', 'add((((a + b) + ((c * d) / f)) + g))'],
    ['a * [1, 2, 3, 4][b * c] * d', '((a * ([1, 2, 3, 4][(b * c)])) * d)'],
    ['add(a * b[2], b[1], 2 * [1, 2][1])', 'add((a * (b[2])), (b[1]), (2 * ([1, 2][1])))'],
]);

it('parses if expressions', function ($input, $condition, $consequence, $alternative) {
    $program = createProgram($input);
    expect($program->statements[0])->toBeExpressionStatement(IfExpression::class, $condition, $consequence, $alternative);
})->with([
    'simple' => [
        'if (true) { x; };',
        [Boolean::class, true],
        [[Identifier::class, 'x']],
        [],
    ],
    'complex' => [
        'if (x < y) { x; };',
        [InfixExpression::class, [Identifier::class, 'x'], '<', [Identifier::class, 'y']],
        [[Identifier::class, 'x']],
        [],
    ],
    'else' => [
        'if (x < y) { x; } else { y; };',
        [InfixExpression::class, [Identifier::class, 'x'], '<', [Identifier::class, 'y']],
        [[Identifier::class, 'x']],
        [[Identifier::class, 'y']],
    ],
]);

it('parses function literals', function ($input, $parameters, $statements) {
    $program = createProgram($input);
    expect($program->statements[0])->toBeExpressionStatement(FunctionLiteral::class, $parameters, $statements);
})->with([
    'classic' => [
        'fn(x, y) { x + y; };',
        [[Identifier::class, 'x'], [Identifier::class, 'y']],
        [[InfixExpression::class, [Identifier::class, 'x'], '+', [Identifier::class, 'y']]],
    ],
    'no parameters' => [
        'fn() {};',
        [],
        [],
    ],
    'one parameter' => [
        'fn(x) {};',
        [[Identifier::class, 'x']],
        [],
    ],
    'many parameter' => [
        'fn(x, y, z) {};',
        [[Identifier::class, 'x'], [Identifier::class, 'y'], [Identifier::class, 'z']],
        [],
    ],
]);

it('parses call expressions', function ($input, $expression, $arguments) {
    $program = createProgram($input);
    expect($program->statements[0])->toBeExpressionStatement(CallExpression::class, $expression, $arguments);
})->with([
    'simple' => [
        'add(1, 2 * 3, 4 + 5)',
        [Identifier::class, 'add'],
        [
            [IntegerLiteral::class, 1],
            [InfixExpression::class, [IntegerLiteral::class, 2], '*', [IntegerLiteral::class, 3]],
            [InfixExpression::class, [IntegerLiteral::class, 4], '+', [IntegerLiteral::class, 5]],
        ],
    ],
    'complex' => [
        'fn() {}()',
        [FunctionLiteral::class, [], []],
        [],
    ],
]);

it('parses array literals', function ($input, $elements) {
    $program = createProgram($input);
    expect($program->statements[0])->toBeExpressionStatement(ArrayLiteral::class, $elements);
})->with([
    [
        '[1, 2 * 2, 3 + 3]',
        [
            [IntegerLiteral::class, 1],
            [InfixExpression::class, [IntegerLiteral::class, 2], '*', [IntegerLiteral::class, 2]],
            [InfixExpression::class, [IntegerLiteral::class, 3], '+', [IntegerLiteral::class, 3]],
        ],
    ],
    [
        '[]',
        [],
    ],
]);

it('parses index expressions', function ($input, $left, $index) {
    $program = createProgram($input);
    expect($program->statements[0])->toBeExpressionStatement(IndexExpression::class, $left, $index);
})->with([
    [
        'myArray[1 + 1]',
        [Identifier::class, 'myArray'],
        [InfixExpression::class, [IntegerLiteral::class, 1], '+', [IntegerLiteral::class, 1]],
    ],
]);

it('parses hash literals', function ($input, $pairs) {
    $program = createProgram($input);
    expect($program->statements[0])->toBeExpressionStatement(HashLiteral::class, $pairs);
})->with([
    [
        '{"one": 1, "two": 2, "three": 3}',
        [
            [[StringLiteral::class, 'one'], [IntegerLiteral::class, 1]],
            [[StringLiteral::class, 'two'], [IntegerLiteral::class, 2]],
            [[StringLiteral::class, 'three'], [IntegerLiteral::class, 3]],
        ],
    ],
    [
        '{}',
        [],
    ],
    [
        '{"one": 1 + 1}',
        [
            [
                [StringLiteral::class, 'one'],
                [InfixExpression::class, [IntegerLiteral::class, 1], '+', [IntegerLiteral::class, 1]],
            ],
        ],
    ],
]);

it('parses macros', function () {
    $program = createProgram('macro(x, y) { x + y; };');
    expect($program->statements[0])->toBeExpressionStatement(
        MacroLiteral::class,
        [
            [Identifier::class, 'x'],
            [Identifier::class, 'y'],
        ],
        [
            [InfixExpression::class, [Identifier::class, 'x'], '+', [Identifier::class, 'y']],
        ],
    );
});

it('parses match', function () {
    $program = createProgram('match (a) { 1 -> true, "one" -> 2 + 2, ? -> "default" };');
    expect($program->statements[0])->toBeExpressionStatement(
        MatchLiteral::class,
        [Identifier::class, 'a'],
        [
            [[IntegerLiteral::class, 1], [Boolean::class, true]],
            [[StringLiteral::class, 'one'], [InfixExpression::class, [IntegerLiteral::class, 2], '+', [IntegerLiteral::class, 2]]],
        ],
        [StringLiteral::class, 'default'],
    );
});

it('parses function literals with name', function () {
    $program = createProgram('let myFunction = fn() {}');

    expect($program->statements[0])->toBeLetStatement('myFunction', [FunctionLiteral::class, [], [], 'myFunction']);
});
