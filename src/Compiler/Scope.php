<?php

namespace Monkey\Compiler;

enum Scope
{
    case GLOBAL;
    case LOCAL;
    case BUILTIN;
}
