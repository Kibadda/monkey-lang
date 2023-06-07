<?php

namespace Monkey\Code;

class Definition
{
    public function __construct(
        public string $name,
        public array $operandWidths,
    ) {
    }
}
