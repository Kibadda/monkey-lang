<?php

namespace Monkey\Evaluator\Object;

enum EvalType
{
    case INTEGER;
    case BOOLEAN;
    case NULL;
    case RETURN;
    case ERROR;
    case FUNCTION;
    case STRING;
    case BUILTIN;
    case ARRAY;
    case HASH;
    case QUOTE;
    case MACRO;
    case COMPILED_FUNCTION;
}
