<?php

namespace Monkey\Object;

use Closure;

class EvalBuiltin implements EvalObject
{
    public function __construct(
        public Closure $builtinFunction,
    ) {
    }

    public function type(): EvalType
    {
        return EvalType::BUILTIN;
    }

    public function inspect(): string
    {
        return "builtin function";
    }
}
