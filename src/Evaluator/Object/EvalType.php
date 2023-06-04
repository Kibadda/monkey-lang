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
}
