<?php

namespace Monkey\Object;

use Monkey\Compiler\Instructions;

class EvalCompiledFunction implements EvalObject
{
    public function __construct(
        public Instructions $instructions,
        public int $numLocals,
        public int $numParameters,
    ) {
    }

    public function type(): EvalType
    {
        return EvalType::COMPILED_FUNCTION;
    }

    public function inspect(): string
    {
        return "CompiledFunction[{$this->type()->name}]";
    }
}
