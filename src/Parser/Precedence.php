<?php

namespace Monkey\Parser;

use Monkey\Token\Type;

enum Precedence: int
{
    case LOWEST = 0;
    case EQUALS = 1;
    case LESSGREATER = 2;
    case SUM = 3;
    case PRODUCT = 4;
    case PREFIX = 5;
    case CALL = 6;

    public static function fromType(Type $type): self
    {
        return match ($type) {
            Type::EQ => self::EQUALS,
            Type::NOT_EQ => self::EQUALS,
            Type::LT => self::LESSGREATER,
            Type::GT => self::LESSGREATER,
            Type::PLUS => self::SUM,
            Type::MINUS => self::SUM,
            Type::SLASH => self::PRODUCT,
            Type::ASTERISK => self::PRODUCT,
            default => self::LOWEST,
        };
    }
}
