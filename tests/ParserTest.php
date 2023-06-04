<?php

use Monkey\Ast\Expression\Boolean;
use Monkey\Ast\Expression\Expression;
use Monkey\Ast\Expression\Identifier;
use Monkey\Ast\Expression\InfixExpression;
use Monkey\Ast\Expression\IntegerLiteral;
use Monkey\Ast\Expression\PrefixExpression;
use Monkey\Ast\Program;
use Monkey\Ast\Statement\ExpressionStatement;
use Monkey\Ast\Statement\LetStatement;
use Monkey\Ast\Statement\ReturnStatement;
use Monkey\Ast\Statement\Statement;
use Monkey\Lexer\Lexer;
use Monkey\Parser\Parser;
use Monkey\Token\Token;
use Monkey\Token\Type;

it('parses let statements correctly', function () {
    $input = '
        let x = 5;
        let y = 10;
        let foobar = 838383;
    ';

    $lexer = Lexer::new($input);
    $parser = Parser::new($lexer);

    $program = $parser->parseProgam();

    expect($parser->errors)->toHaveCount(0);

    expect($program)->not->toBe(null);
    expect($program->statements)->toHaveCount(3);

    $tests = [
        'x',
        'y',
        'foobar',
    ];

    foreach ($tests as $i => $test) {
        expect($program->statements[$i])->toBeLetStatement($test);
    }
});

it('parses return statements correctly', function () {
    $input = '
        return 5;
        return 10;
        return 838383;
    ';

    $lexer = Lexer::new($input);
    $parser = Parser::new($lexer);

    $program = $parser->parseProgam();

    expect($parser->errors)->toHaveCount(0);

    expect($program)->not->toBe(null);
    expect($program->statements)->toHaveCount(3);

    foreach ($program->statements as $stmt) {
        expect($stmt)->toBeReturnStatement();
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
    ];

    foreach ($tests as $test) {
        $lexer = Lexer::new($test[0]);
        $parser = Parser::new($lexer);
        $program = $parser->parseProgam();

        expect($parser->errors)->toHaveCount(0);

        expect($program->string())->toBe($test[1]);
    }
});