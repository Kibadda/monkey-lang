<?php

namespace Monkey\Token;

use Monkey\Parser\Precedence;

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
    case ARROW;
    case QUESTION;

    case FUNCTION;
    case LET;
    case TRUE;
    case FALSE;
    case IF;
    case ELSE;
    case RETURN;
    case MATCH;

    case MACRO;

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
            'macro' => self::MACRO,
            'match' => self::MATCH,
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

    public function precedence(): Precedence
    {
        return match ($this) {
            self::EQ => Precedence::EQUALS,
            self::NOT_EQ => Precedence::EQUALS,
            self::LT => Precedence::LESSGREATER,
            self::GT => Precedence::LESSGREATER,
            self::PLUS => Precedence::SUM,
            self::MINUS => Precedence::SUM,
            self::SLASH => Precedence::PRODUCT,
            self::ASTERISK => Precedence::PRODUCT,
            self::LPAREN => Precedence::CALL,
            self::LBRACKET => Precedence::INDEX,
            default => Precedence::LOWEST,
        };
    }
}
