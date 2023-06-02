<?php

namespace Lexer\Lib;

enum Type
{
    case ILLEGAL;
    case EOL;

    case IDENTIFIER;
    case INT;

    case ASSIGN;
    case PLUS;
    case COMMA;
    case SEMICOLON;
    case LPAREN;
    case RPAREN;
    case LBRACE;
    case RBRACE;

    case FUNCITON;
    case LET;

    public static function lookup(string $identifier): self
    {
        return match (strtoupper($identifier)) {
            self::FUNCITON->name => self::FUNCITON,
            self::LET->name => self::LET,
            default => self::IDENTIFIER,
        };
    }
}
