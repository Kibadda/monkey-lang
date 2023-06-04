<?php

use Monkey\Ast\Expression\Boolean;
use Monkey\Ast\Expression\CallExpression;
use Monkey\Ast\Expression\FunctionLiteral;
use Monkey\Ast\Expression\Identifier;
use Monkey\Ast\Expression\IfExpression;
use Monkey\Ast\Expression\InfixExpression;
use Monkey\Ast\Expression\IntegerLiteral;
use Monkey\Ast\Expression\PrefixExpression;
use Monkey\Ast\Program;
use Monkey\Ast\Statement\LetStatement;
use Monkey\Lexer\Lexer;
use Monkey\Parser\Parser;
use Monkey\Token\Token;
use Monkey\Token\Type;

function createProgram($input): Program
{
    $lexer = Lexer::new($input);
    $parser = Parser::new($lexer);
    $program = $parser->parseProgam();

    expect($parser->errors)->toHaveCount(0);
    expect($program->statements)->toHaveCount(1);

    return $program;
}

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
