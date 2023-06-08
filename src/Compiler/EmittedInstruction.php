<?php

namespace Monkey\Compiler;

use Monkey\Code\Code;

class EmittedInstruction
{
    public function __construct(
        public Code $code,
        public int $position,
    ) {
    }
}
