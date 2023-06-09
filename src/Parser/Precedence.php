<?php

namespace Monkey\Parser;

enum Precedence: int
{
    case LOWEST = 0;
    case EQUALS = 1;
    case LESSGREATER = 2;
    case SUM = 3;
    case PRODUCT = 4;
    case PREFIX = 5;
    case CALL = 6;
    case INDEX = 7;
}
