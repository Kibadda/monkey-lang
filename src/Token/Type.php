<?php

namespace Monkey\Token;

enum Type
{
    case ILLEGAL;
    case EOF;

    case IDENTIFIER;
    case INT;
    case STRING;

    case ASSIGN;
    case PLUS;
    case MINUS;
    case SLASH;
    case ASTERISK;
    case BANG;

    case LT;
    case GT;
    case EQ;
    case NOT_EQ;

    case COMMA;
    case SEMICOLON;
    case LPAREN;
    case RPAREN;
    case LBRACE;
    case RBRACE;
    case LBRACKET;
    case RBRACKET;
    case COLON;

    case FUNCTION;
    case LET;
    case TRUE;
    case FALSE;
    case IF;
    case ELSE;
    case RETURN;

    public static function lookup(string $identifier): self
    {
        return match ($identifier) {
            'fn' => self::FUNCTION,
            'let' => self::LET,
            'true' => self::TRUE,
            'false' => self::FALSE,
            'if' => self::IF,
            'else' => self::ELSE,
            'return' => self::RETURN,
            default => self::IDENTIFIER,
        };
    }

    public function literal(): ?string
    {
        return match ($this) {
            self::ILLEGAL => null,
            self::EOF => '',
            self::ASSIGN => '=',
            self::PLUS => '+',
            self::COMMA => ',',
            self::SEMICOLON => ';',
            self::LPAREN => '(',
            self::RPAREN => ')',
            self::LBRACE => '{',
            self::RBRACE => '}',
            default => '',
        };
    }
}
