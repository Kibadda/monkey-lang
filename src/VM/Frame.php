<?php

namespace Monkey\VM;

use Monkey\Compiler\Instructions;
use Monkey\Object\EvalCompiledFunction;

class Frame
{
    public function __construct(
        public EvalCompiledFunction $function,
        public int $basePointer,
        public int $ip = -1,
    ) {
    }

    public function instructions(): Instructions
    {
        return $this->function->instructions;
    }
}
