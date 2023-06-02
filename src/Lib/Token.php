<?php

namespace Lexer\Lib;

class Token
{
    public function __construct(
        public Type $type,
        public string $literal,
    ) {
    }
}
