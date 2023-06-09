<?php

use Monkey\Lexer\Lexer;
use Monkey\Token\Token;
use Monkey\Token\Type;

it('tokenizes input', function () {
    $input = '
        let five = 5;
        let ten = 10;

        let add = fn(x, y) {
          x + y;
        };

        let result = add(five, ten);

        if (5 < 10) {
            return true;
        } else {
            return false;
        };

        10 == 10;
        10 != 9;
        
        !((5 + 5) == 10);

        "foobar";
        "foo bar";

        [1, 2];

        {"foo": "bar"};

        macro(x, y) { x + y; };
    ';

    $tests = [
        new Token(Type::LET, 'let'),
        new Token(Type::IDENTIFIER, 'five'),
        new Token(Type::ASSIGN, '='),
        new Token(Type::INT, '5'),
        new Token(Type::SEMICOLON, ';'),
        new Token(Type::LET, 'let'),
        new Token(Type::IDENTIFIER, 'ten'),
        new Token(Type::ASSIGN, '='),
        new Token(Type::INT, '10'),
        new Token(Type::SEMICOLON, ';'),
        new Token(Type::LET, 'let'),
        new Token(Type::IDENTIFIER, 'add'),
        new Token(Type::ASSIGN, '='),
        new Token(Type::FUNCTION, 'fn'),
        new Token(Type::LPAREN, '('),
        new Token(Type::IDENTIFIER, 'x'),
        new Token(Type::COMMA, ','),
        new Token(Type::IDENTIFIER, 'y'),
        new Token(Type::RPAREN, ')'),
        new Token(Type::LBRACE, '{'),
        new Token(Type::IDENTIFIER, 'x'),
        new Token(Type::PLUS, '+'),
        new Token(Type::IDENTIFIER, 'y'),
        new Token(Type::SEMICOLON, ';'),
        new Token(Type::RBRACE, '}'),
        new Token(Type::SEMICOLON, ';'),
        new Token(Type::LET, 'let'),
        new Token(Type::IDENTIFIER, 'result'),
        new Token(Type::ASSIGN, '='),
        new Token(Type::IDENTIFIER, 'add'),
        new Token(Type::LPAREN, '('),
        new Token(Type::IDENTIFIER, 'five'),
        new Token(Type::COMMA, ','),
        new Token(Type::IDENTIFIER, 'ten'),
        new Token(Type::RPAREN, ')'),
        new Token(Type::SEMICOLON, ';'),
        new Token(Type::IF, 'if'),
        new Token(Type::LPAREN, '('),
        new Token(Type::INT, '5'),
        new Token(Type::LT, '<'),
        new Token(Type::INT, '10'),
        new Token(Type::RPAREN, ')'),
        new Token(Type::LBRACE, '{'),
        new Token(Type::RETURN, 'return'),
        new Token(Type::TRUE, 'true'),
        new Token(Type::SEMICOLON, ';'),
        new Token(Type::RBRACE, '}'),
        new Token(Type::ELSE, 'else'),
        new Token(Type::LBRACE, '{'),
        new Token(Type::RETURN, 'return'),
        new Token(Type::FALSE, 'false'),
        new Token(Type::SEMICOLON, ';'),
        new Token(Type::RBRACE, '}'),
        new Token(Type::SEMICOLON, ';'),
        new Token(Type::INT, '10'),
        new Token(Type::EQ, '=='),
        new Token(Type::INT, '10'),
        new Token(Type::SEMICOLON, ';'),
        new Token(Type::INT, '10'),
        new Token(Type::NOT_EQ, '!='),
        new Token(Type::INT, '9'),
        new Token(Type::SEMICOLON, ';'),
        new Token(Type::BANG, '!'),
        new Token(Type::LPAREN, '('),
        new Token(Type::LPAREN, '('),
        new Token(Type::INT, '5'),
        new Token(Type::PLUS, '+'),
        new Token(Type::INT, '5'),
        new Token(Type::RPAREN, ')'),
        new Token(Type::EQ, '=='),
        new Token(Type::INT, '10'),
        new Token(Type::RPAREN, ')'),
        new Token(Type::SEMICOLON, ';'),
        new Token(Type::STRING, 'foobar'),
        new Token(Type::SEMICOLON, ';'),
        new Token(Type::STRING, 'foo bar'),
        new Token(Type::SEMICOLON, ';'),
        new Token(Type::LBRACKET, '['),
        new Token(Type::INT, '1'),
        new Token(Type::COMMA, ','),
        new Token(Type::INT, '2'),
        new Token(Type::RBRACKET, ']'),
        new Token(Type::SEMICOLON, ';'),
        new Token(Type::LBRACE, '{'),
        new Token(Type::STRING, 'foo'),
        new Token(Type::COLON, ':'),
        new Token(Type::STRING, 'bar'),
        new Token(Type::RBRACE, '}'),
        new Token(Type::SEMICOLON, ';'),
        new Token(Type::MACRO, 'macro'),
        new Token(Type::LPAREN, '('),
        new Token(Type::IDENTIFIER, 'x'),
        new Token(Type::COMMA, ','),
        new Token(Type::IDENTIFIER, 'y'),
        new Token(Type::RPAREN, ')'),
        new Token(Type::LBRACE, '{'),
        new Token(Type::IDENTIFIER, 'x'),
        new Token(Type::PLUS, '+'),
        new Token(Type::IDENTIFIER, 'y'),
        new Token(Type::SEMICOLON, ';'),
        new Token(Type::RBRACE, '}'),
        new Token(Type::SEMICOLON, ';'),
        new Token(Type::EOF, ''),
    ];

    $lexer = new Lexer($input);

    foreach ($tests as $test) {
        $token = $lexer->nextToken();

        expect($token->type->name)->toBe($test->type->name);
        expect($token->literal)->toBe($test->literal);
    }
});
