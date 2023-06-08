<?php

namespace Monkey\Compiler;

class Symbol
{
    public function __construct(
        public string $name,
        public Scope $scope,
        public int $index,
    ) {
    }
}
