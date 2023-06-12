<?php

namespace Monkey\Code;

class Definition
{
    /**
     * @param int[] $operandWidths
     */
    public function __construct(
        public string $name,
        public array $operandWidths,
    ) {
    }
}
