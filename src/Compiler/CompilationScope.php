<?php

namespace Monkey\Compiler;

class CompilationScope
{
    public function __construct(
        public Instructions $instructions = new Instructions([]),
        public ?EmittedInstruction $lastInstruction = null,
        public ?EmittedInstruction $previousInstruction = null,
    ) {
    }
}
