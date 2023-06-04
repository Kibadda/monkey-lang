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

it('parses let statements correctly', function () {
    $tests = [
        ['let x = 5;', 'x', [IntegerLiteral::class, 5]],
        ['let y = true;', 'y', [Boolean::class, true]],
        ['let foobar = y;', 'foobar', [Identifier::class, 'y']],
    ];

    foreach ($tests as $test) {
        $lexer = Lexer::new($test[0]);
        $parser = Parser::new($lexer);
        $program = $parser->parseProgam();

        expect($parser->errors)->toHaveCount(0);
        expect($program->statements)->toHaveCount(1);

        expect($program->statements[0])->toBeLetStatement(
            $test[1],
            $test[2],
        );
    }
});

it('parses return statements correctly', function () {
    $tests = [
        ['return 5;', [IntegerLiteral::class, 5]],
        ['return true;', [Boolean::class, true]],
        ['return foobar;', [Identifier::class, 'foobar']],
    ];

    foreach ($tests as $test) {
        $lexer = Lexer::new($test[0]);
        $parser = Parser::new($lexer);
        $program = $parser->parseProgam();

        expect($parser->errors)->toHaveCount(0);
        expect($program->statements)->toHaveCount(1);

        expect($program->statements[0])->toBeReturnStatement(
            $test[1],
        );
    }
});

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

it('parses identitier expressions', function () {
    $input = 'foobar;';

    $lexer = Lexer::new($input);
    $parser = Parser::new($lexer);
    $program = $parser->parseProgam();

    expect($parser->errors)->toHaveCount(0);
    expect($program->statements)->toHaveCount(1);

    expect($program->statements[0])->toBeExpressionStatement(Identifier::class, 'foobar');
});

it('parses integer literal expressions', function () {
    $input = '5;';

    $lexer = Lexer::new($input);
    $parser = Parser::new($lexer);
    $program = $parser->parseProgam();

    expect($parser->errors)->toHaveCount(0);
    expect($program->statements)->toHaveCount(1);

    expect($program->statements[0])->toBeExpressionStatement(IntegerLiteral::class, 5);
});

it('parses prefix expression', function () {
    $tests = [
        ['!5;', '!', 5],
        ['-15;', '-', 15],
    ];

    foreach ($tests as $test) {
        $lexer = Lexer::new($test[0]);
        $parser = Parser::new($lexer);
        $program = $parser->parseProgam();

        expect($parser->errors)->toHaveCount(0);
        expect($program->statements)->toHaveCount(1);

        expect($program->statements[0])->toBeExpressionStatement(PrefixExpression::class, $test[1], [IntegerLiteral::class, $test[2]]);
    }
});

it('parses infix expression', function () {
    $tests = [
        ['5 + 5;', 5, '+', 5],
        ['5 - 5;', 5, '-', 5],
        ['5 * 5;', 5, '*', 5],
        ['5 / 5;', 5, '/', 5],
        ['5 > 5;', 5, '>', 5],
        ['5 < 5;', 5, '<', 5],
        ['5 == 5;', 5, '==', 5],
        ['5 != 5;', 5, '!=', 5],
    ];

    foreach ($tests as $test) {
        $lexer = Lexer::new($test[0]);
        $parser = Parser::new($lexer);
        $program = $parser->parseProgam();

        expect($parser->errors)->toHaveCount(0);
        expect($program->statements)->toHaveCount(1);

        expect($program->statements[0])->toBeExpressionStatement(InfixExpression::class, [IntegerLiteral::class, $test[1]], $test[2], [IntegerLiteral::class, $test[3]]);
    }
});

it('parses boolean expressions', function () {
    $input = 'true;';

    $lexer = Lexer::new($input);
    $parser = Parser::new($lexer);
    $program = $parser->parseProgam();

    expect($parser->errors)->toHaveCount(0);
    expect($program->statements)->toHaveCount(1);

    expect($program->statements[0])->toBeExpressionStatement(Boolean::class, true);
});

it('parses operator precedence', function () {
    $tests = [
        ['-a * b', '((-a) * b)'],
        ['!-a', '(!(-a))'],
        ['a + b + c', '((a + b) + c)'],
        ['a + b - c', '((a + b) - c)'],
        ['a * b * c', '((a * b) * c)'],
        ['a * b / c', '((a * b) / c)'],
        ['a + b / c', '(a + (b / c))'],
        ['a + b * c + d / e - f', '(((a + (b * c)) + (d / e)) - f)'],
        ['3 + 4; -5 * 5;', '(3 + 4)((-5) * 5)'],
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
    ];

    foreach ($tests as $test) {
        $lexer = Lexer::new($test[0]);
        $parser = Parser::new($lexer);
        $program = $parser->parseProgam();

        expect($parser->errors)->toHaveCount(0);

        expect($program->string())->toBe($test[1]);
    }
});

it('parses if expressions', function () {
    $input = 'if (x < y) { x; };';

    $lexer = Lexer::new($input);
    $parser = Parser::new($lexer);
    $program = $parser->parseProgam();

    expect($parser->errors)->toHaveCount(0);
    expect($program->statements)->toHaveCount(1);

    expect($program->statements[0])->toBeExpressionStatement(
        IfExpression::class,
        [InfixExpression::class, [Identifier::class, 'x'], '<', [Identifier::class, 'y']],
        [[Identifier::class, 'x']],
        [],
    );
});

it('parses if else expressions', function () {
    $input = 'if (x < y) { x; } else { y; };';

    $lexer = Lexer::new($input);
    $parser = Parser::new($lexer);
    $program = $parser->parseProgam();

    expect($parser->errors)->toHaveCount(0);
    expect($program->statements)->toHaveCount(1);

    expect($program->statements[0])->toBeExpressionStatement(
        IfExpression::class,
        [InfixExpression::class, [Identifier::class, 'x'], '<', [Identifier::class, 'y']],
        [[Identifier::class, 'x']],
        [[Identifier::class, 'y']],
    );
});

it('parses function literals', function () {
    $input = 'fn(x, y) { x + y; };';

    $lexer = Lexer::new($input);
    $parser = Parser::new($lexer);
    $program = $parser->parseProgam();

    expect($parser->errors)->toHaveCount(0);
    expect($program->statements)->toHaveCount(1);

    expect($program->statements[0])->toBeExpressionStatement(
        FunctionLiteral::class,
        [[Identifier::class, 'x'], [Identifier::class, 'y']],
        [[InfixExpression::class, [Identifier::class, 'x'], '+', [Identifier::class, 'y']]],
    );
});

it('parses function parameters', function () {
    $tests = [
        ['fn() {};', []],
        ['fn(x) {};', ['x']],
        ['fn(x, y, z) {};', ['x', 'y', 'z']],
    ];

    foreach ($tests as $test) {
        $lexer = Lexer::new($test[0]);
        $parser = Parser::new($lexer);
        $program = $parser->parseProgam();

        expect($parser->errors)->toHaveCount(0);
        expect($program->statements)->toHaveCount(1);

        expect($program->statements[0])->toBeExpressionStatement(
            FunctionLiteral::class,
            array_map(fn ($identifier) => [Identifier::class, $identifier], $test[1]),
            [],
        );
    }
});

it('parses call expressions', function () {
    $input = 'add(1, 2 * 3, 4 + 5)';

    $lexer = Lexer::new($input);
    $parser = Parser::new($lexer);
    $program = $parser->parseProgam();

    expect($parser->errors)->toHaveCount(0);
    expect($program->statements)->toHaveCount(1);

    expect($program->statements[0])->toBeExpressionStatement(
        CallExpression::class,
        [Identifier::class, 'add'],
        [
            [IntegerLiteral::class, 1],
            [InfixExpression::class, [IntegerLiteral::class, 2], '*', [IntegerLiteral::class, 3]],
            [InfixExpression::class, [IntegerLiteral::class, 4], '+', [IntegerLiteral::class, 5]],
        ],
    );
});
