<?php

namespace Monkey\VM;

use Monkey\Compiler\Instructions;
use Monkey\Object\EvalClosure;

class Frame
{
    public function __construct(
        public EvalClosure $closure,
        public int $basePointer,
        public int $ip = -1,
    ) {
    }

    public function instructions(): Instructions
    {
        return $this->closure->function->instructions;
    }
}
